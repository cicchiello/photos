#!/usr/bin/python3

import os
import calendar
import time
import datetime
import hashlib
import json
import requests
import subprocess

from os.path import exists
from requests.auth import HTTPBasicAuth

from lucenize import Lucenize
from tagset import Tagset


def nowstr():
    return datetime.datetime.today().strftime('%Y-%b-%d %H:%M:%S')


def md5(fname):
    hash_md5 = hashlib.md5()
    with open(fname, "rb") as f:
        for chunk in iter(lambda: f.read(4096), b""):
            hash_md5.update(chunk)
    return hash_md5.hexdigest()


class Uploader():
    def __init__(self, db, path, creds, verbose=False):
        self._verbose = verbose
        self._doc_id = md5(path)
        self._doc_url = "%s/%s" % (db, self._doc_id)
        self._creds = creds
        self._tagset = Tagset()
        self._tagset.append_metadata_tags(path)
        self._doc = {}
        self._doc['paths'] = [path]
        self._doc['image_type'] = "image/%s" % path.split(".")[-1]
        self._doc['upload_timestamp'] = calendar.timegm(time.gmtime())


    def recognize(self):
        _original_path = self._doc['paths'][0]
        _path = _original_path
        _cnt = 0
        while len(open(_path, 'rb').read()) > 4000000:
            # have to scale the image down to size
            # > convert <given-file> -scale 50% <halved-file>
            _newpath = "/tmp/%s_%s" % (_cnt, os.path.basename(_original_path))
            _cnt += 1
            if self._verbose:
                print("DEBUG(%s:%s): scaling(50%%):  %s => %s" % (__name__, nowstr(), _path, _newpath))
            if exists(_newpath):
                if self._verbose:
                    print("DEBUG(%s:%s): removing: %s" % (__name__, nowstr(), _newpath))
                os.remove(_newpath)
            subprocess.call(["convert", _path, "-scale", "50%", _newpath])
            if not exists(_newpath):
                print("ERROR(%s:%s): failed to create %s" % (__name__, nowstr(), _newpath))
                exit(-1)
            if exists(_path) and (_path != _original_path):
                if self._verbose:
                    print("DEBUG(%s:%s): removing: %s" % (__name__, nowstr(), _path))
                os.remove(_path)
            _path = _newpath
            
        if self._verbose:
            print("DEBUG(%s:%s): calling AWS Rekognition with: %s" % (__name__, nowstr(), _path))
        self._tagset.recognize(_path)
        
        if exists(_path) and (_path != _original_path):
            if self._verbose:
                print("DEBUG(%s:%s): removing: %s" % (__name__, nowstr(), _path))
            os.remove(_path)

        
    def createEntry(self):
        if self._verbose:
            print("DEBUG(%s:%s): creating CouchDb document(%s)" % (__name__, nowstr(), self._doc_id))
            
        _path = self._doc['paths'][0]
        
        _headers = {"Content-Type": "application/json"}
        self._doc['tags'] = self._tagset.get_tag_arr()
        _attachmentData = open(_path, 'rb').read()
        self._doc['size'] = len(_attachmentData)
        _r = requests.put(self._doc_url, json=self._doc, headers=_headers)
        
        if b'conflict' in _r.content:
            print("ERROR(%s:%s): conflict" % (__name__, nowstr()))
            exit(-2)

        _rev = json.loads(_r.content)["rev"]
        self._doc['_rev'] = _rev
        _attachmentUrl = "%s/image?rev=%s" % (self._doc_url, _rev)
        _attachmentHeaders = {"Content-Type": "image/%s" % _path.split(".")[-1]}
        
        if self._verbose:
            print("DEBUG(%s:%s): attaching image to document(%s)" % (__name__, nowstr(), self._doc_id))
        _r = requests.put(_attachmentUrl, data=_attachmentData, headers=_attachmentHeaders)
        _rev = json.loads(_r.content)["rev"]

        # > convert <given-file> -resize 640 <web-suitable-file>
        _web_suitable_path = "/tmp/%s" % os.path.basename(_path)
        
        if self._verbose:
            print("DEBUG(%s:%s): createing web suitable image: %s" % (__name__, nowstr(), _web_suitable_path))
            
        subprocess.call(["convert", _path, "-resize", "640", _web_suitable_path])
        _web_suitable_attachmentUrl = "%s/web_image?rev=%s" % (self._doc_url, _rev)
        _web_suitable_attachmentHeaders = {"Content-Type": "image/%s" % _path.split(".")[-1]}
        
        if self._verbose:
            print("DEBUG(%s:%s): attaching web-suitable image to document(%s)" % (__name__, nowstr(), self._doc_id))
        _web_suitable_attachmentData = open(_web_suitable_path, 'rb').read()
        _r = requests.put(_web_suitable_attachmentUrl, data=_web_suitable_attachmentData, headers=_attachmentHeaders)

    

    def updateEntry(self):
        if self._verbose:
            print("DEBUG(%s:%s): updating CouchDb document(%s)" % (__name__, nowstr(), self._doc_id))
            
        _doc = self.downloadDoc(self._doc_url)
        self._tagset = Tagset.merge(Tagset(tag_arr=_doc['tags']), self._tagset)
        self._doc['paths'] = list(set(_doc['paths']).union(self._doc['paths']))
        self._doc['tags'] = self._tagset.get_tag_arr()
        self._doc['size'] = _doc['size']
        self._doc['_rev'] = _doc['_rev']
        
        _headers = {"Content-Type": "application/json"}
        return requests.put(self._doc_url, json=self._doc, headers=_headers)
    

    def downloadDoc(self, url):
        _headers = {"Content-Type": "application/json"}
        _r = requests.get(url, headers=_headers)
        return json.loads(_r.content) if _r.status_code == 200 else None
    

    def docExists(self):
        return not self.downloadDoc(self._doc_url) is None


    
if __name__ == "__main__":
    import sys
    import argparse
    
    from argparse import RawTextHelpFormatter
    
    _description = 'upload_pics.py photo uploader'
    _epilog = '\n\nThis uploads the given photo and metadata\n\n'
    _parser = argparse.ArgumentParser(prog=sys.argv[0], description=_description, \
                                      epilog=_epilog, formatter_class=RawTextHelpFormatter)

    _parser.add_argument('-db', nargs='?', required=True, help='path to CouchDb db')
    _parser.add_argument('-creds', nargs='?', required=True, help='CouchDb db credentials (user:pswd)')
    _parser.add_argument('-pic', nargs='?', required=True, help='path to image file')
    _parser.add_argument('-verbose', default=False, action='store_true', help='provide extra debug output')
    
    _args = _parser.parse_args(args=sys.argv[1:])

    if not exists(_args.pic):
        print("\nERROR(%s:%s): %s not found\n" % (__name__, nowstr(), _args.pic))
        _parser.print_help()
        exit()
    
    print("ECHO(%s:%s): db: %s" % (__name__, nowstr(), _args.db))
    print("ECHO(%s:%s): pic: %s" % (__name__, nowstr(), _args.pic))
    print("ECHO(%s:%s): verbose: %s" % (__name__, nowstr(), _args.verbose))

    _u = Uploader(_args.db, _args.pic, _args.creds.split(":"), verbose=_args.verbose)
    _u.recognize()
    _u.updateEntry() if _u.docExists() else _u.createEntry()
    
