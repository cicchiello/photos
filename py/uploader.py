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
    def __init__(self, db, path, creds):
        self._doc_url = "%s/%s" % (db, md5(path))
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
        print("TRACE(%s:%s): _path: %s" % (__name__, nowstr(), _path))
        while len(open(_path, 'rb').read()) > 4000000:
            # have to scale the image down to size
            # > convert <given-file> -scale 50% <halved-file>
            _newpath = "/tmp/%s_%s" % (_cnt, os.path.basename(_original_path))
            _cnt += 1
            print("TRACE(%s:%s): _newpath: %s" % (__name__, nowstr(), _newpath))
            if exists(_newpath):
                print("TRACE(%s:%s): removing _newpath: %s" % (__name__, nowstr(), _newpath))
                os.remove(_newpath)
            subprocess.call(["convert", _path, "-scale", "50%", _newpath])
            if exists(_newpath):
                print("TRACE(%s:%s): just created _newpath: %s" % (__name__, nowstr(), _newpath))
            if exists(_path) and (_path != _original_path):
                print("TRACE(%s:%s): removing _path: %s" % (__name__, nowstr(), _path))
                os.remove(_path)
            _path = _newpath
            print("TRACE(%s:%s): _path: %s" % (__name__, nowstr(), _path))
        self._tagset.recognize(_path)
        if exists(_path) and (_path != _original_path):
            print("TRACE(%s:%s): removing _path: %s" % (__name__, nowstr(), _path))
            os.remove(_path)

        
    def createEntry(self):
        _headers = {"Content-Type": "application/json"}
        self._doc['tags'] = self._tagset.get_tag_arr()
        _attachmentData = open(self._doc['paths'][0], 'rb').read()
        self._doc['size'] = len(_attachmentData)
        _r = requests.put(self._doc_url, json=self._doc, headers=_headers)
        
        if b'conflict' in _r.content:
            print("ERROR(%s:%s): conflict" % (__name__, nowstr()))
            exit()

        _rev = json.loads(_r.content)["rev"]
        self._doc['_rev'] = _rev
        _attachmentUrl = "%s/image?rev=%s" % (self._doc_url, _rev)
        _attachmentHeaders = {"Content-Type": "image/%s" % self._doc['paths'][0].split(".")[-1]}
        return requests.put(_attachmentUrl, data=_attachmentData, headers=_attachmentHeaders)
    

    def updateEntry(self):
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
    
    _args = _parser.parse_args(args=sys.argv[1:])

    if not exists(_args.pic):
        print("\nERROR(%s:%s): %s not found\n" % (__name__, nowstr(), _args.pic))
        _parser.print_help()
        exit()
    
    print("ECHO(%s:%s): db: %s" % (__name__, nowstr(), _args.db))
    print("ECHO(%s:%s): pic: %s" % (__name__, nowstr(), _args.pic))

    _u = Uploader(_args.db, _args.pic, _args.creds.split(":"))
    _u.recognize()
    _r = _u.updateEntry() if _u.docExists() else _u.createEntry()
    
