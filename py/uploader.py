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
        self._tagset = Tagset(verbose=self._verbose)
        self._tagset.append_metadata_tags(path)
        self._path = path
        self._doc = {}
        self._doc['paths'] = [path]
        self._doc['type'] = "photo"
        self._doc['image_type'] = "image/%s" % path.split(".")[-1]
        self._doc['upload_timestamp'] = calendar.timegm(time.gmtime())


    def putJsonWithRetries(self, url, jdoc, headers):
        _tries = 0
        _sleep = 0.5
        while _tries < 5:
            try:
                return requests.put(url, json=jdoc, headers=headers)
            except Exception as e:
                print("WARNING(%s:%s): putJsonWithRetries; caught exception: %s" %
                      (__name__, nowstr(), str(e)))
                _tries += 1
                time.sleep(_sleep)
                _sleep *= 2
                
        print("ERROR(%s:%s): putJsonWithRetries; quitting after 5 tries" % (__name__, nowstr()))
        exit(-1)
        
            
    def putBinWithRetries(self, url, data, headers):
        _tries = 0
        _sleep = 0.5
        while _tries < 5:
            try:
                return requests.put(url, data=data, headers=headers)
            except Exception as e:
                print("WARNING(%s:%s): putBinWithRetries; caught exception: %s" %
                      (__name__, nowstr(), str(e)))
                _tries += 1
                time.sleep(_sleep)
                _sleep *= 2
                
        print("ERROR(%s:%s): putBinWithRetries; quitting after 5 tries" % (__name__, nowstr()))
        exit(-1)
        
            
    def getWithRetries(self, url, headers):
        _tries = 0
        _sleep = 0.5
        while _tries < 5:
            try:
                return requests.get(url, headers=headers)
            except Exception as e:
                print("WARNING(%s:%s): getWithRetries; caught exception: %s" %
                      (__name__, nowstr(), str(e)))
                _tries += 1
                time.sleep(_sleep)
                _sleep *= 2
                
        print("ERROR(%s:%s): getWithRetries; quitting after 5 tries" % (__name__, nowstr()))
        exit(-1)
            
            
    def deleteWithRetries(self, url):
        _tries = 0
        _sleep = 0.5
        while _tries < 5:
            try:
                return requests.delete(url)
            except Exception as e:
                print("WARNING(%s:%s): deleteWithRetries; caught exception: %s" %
                      (__name__, nowstr(), str(e)))
                _tries += 1
                time.sleep(_sleep)
                _sleep *= 2
                
        print("ERROR(%s:%s): deleteWithRetries; quitting after 5 tries" % (__name__, nowstr()))
        exit(-1)
            
            
    def headWithRetries(self, url):
        _tries = 0
        _sleep = 0.5
        while _tries < 5:
            try:
                return requests.head(url)
            except Exception as e:
                print("WARNING(%s:%s): headWithRetries; caught exception: %s" %
                      (__name__, nowstr(), str(e)))
                _tries += 1
                time.sleep(_sleep)
                _sleep *= 2
                
        print("ERROR(%s:%s): getWithRetries; quitting after 5 tries" % (__name__, nowstr()))
        exit(-1)
            
            
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
            
        if os.path.getsize(_path) > 0:
            if self._verbose:
                print("DEBUG(%s:%s): calling AWS Rekognition with: %s" % (__name__, nowstr(), _path))
            self._tagset.recognize(_path)
        
            if exists(_path) and (_path != _original_path):
                if self._verbose:
                    print("DEBUG(%s:%s): removing: %s" % (__name__, nowstr(), _path))
                os.remove(_path)
            return True
        else:
            print("WARNING(%s:%s): couldn't recognize invalid file: %s" % (__name__, nowstr(), _path))
            return False
            

    def imageUrl(self, revision):
        return "%s/image?rev=%s" % (self._doc_url, revision)

    
    def attachImage(self, revision):
        # no conversion of original image -- keep original for posterity
        _imageHeaders = {"Content-Type": "image/%s" % self._path.split(".")[-1]}
        _imageData = open(self._path, 'rb').read()

        if self._doc['size'] != len(_imageData):
            print("ERROR(%s:%s): size validation failure in attachImage" % (__name__, nowstr()))
            exit(-3)
            
        if self._verbose:
            print("DEBUG(%s:%s): attaching image to document(%s)" % (__name__, nowstr(), self._doc_id))

        _r = self.putBinWithRetries(self.imageUrl(revision), _imageData, _imageHeaders)
        return json.loads(_r.content)["rev"]


    def webSuitableUrl(self, revision):
        return "%s/web_image?rev=%s" % (self._doc_url, revision)

    
    def attachWebSuitable(self, revision):
        # > convert <given-file> -resize 640 <web-suitable-file>
        _web_suitable_path = "/tmp/%s" % os.path.basename(self._path)
        
        if self._verbose:
            print("DEBUG(%s:%s): creating web suitable image: %s" %
                  (__name__, nowstr(), _web_suitable_path))
            
        subprocess.call(["convert", self._path, "-resize", "640", _web_suitable_path])
        _web_suitable_attachmentHeaders = {"Content-Type": "image/%s" % self._path.split(".")[-1]}

        if self._verbose:
            print("DEBUG(%s:%s): attaching web-suitable image to document(%s)"
                  % (__name__, nowstr(), self._doc_id))
            
        _web_suitable_attachmentData = open(_web_suitable_path, 'rb').read()

        _r = self.putBinWithRetries(self.webSuitableUrl(revision), _web_suitable_attachmentData,
                                    _web_suitable_attachmentHeaders)

        os.remove(_web_suitable_path)
        return json.loads(_r.content)["rev"]

    
    def thumbnailUrl(self, revision):
        return "%s/thumbnail?rev=%s" % (self._doc_url, revision)

    
    def attachThumbnail(self, revision):
        # > convert <given-file> -resize 128 <thumbnail-file>
        _thumbnail_path = "/tmp/thumb_%s" % os.path.basename(self._path)
        
        if self._verbose:
            print("DEBUG(%s:%s): creating thumbnail image: %s" % (__name__, nowstr(), _thumbnail_path))
            
        subprocess.call(["convert", self._path, "-resize", "128", _thumbnail_path])
        _thumbnail_attachmentHeaders = {"Content-Type": "image/%s" % self._path.split(".")[-1]}

        if self._verbose:
            print("DEBUG(%s:%s): attaching thumbnail to document(%s)" % (__name__, nowstr(), self._doc_id))
        _thumbnail_attachmentData = open(_thumbnail_path, 'rb').read()

        _r = self.putBinWithRetries(self.thumbnailUrl(revision), _thumbnail_attachmentData,
                                    _thumbnail_attachmentHeaders)

        os.remove(_thumbnail_path)

        return json.loads(_r.content)["rev"]

    
    def createEntry(self):
        if self._verbose:
            print("DEBUG(%s:%s): creating CouchDb document(%s)" % (__name__, nowstr(), self._doc_id))
            
        _headers = {"Content-Type": "application/json"}
        self._doc['tags'] = self._tagset.get_tag_arr()
        _attachmentData = open(self._path, 'rb').read()
        self._doc['size'] = len(_attachmentData)

        _r = self.putJsonWithRetries(self._doc_url, self._doc, _headers)
        
        if b'conflict' in _r.content:
            print("ERROR(%s:%s): conflict" % (__name__, nowstr()))
            exit(-2)

        _rev = json.loads(_r.content)["rev"]
        _rev = self.attachImage(_rev)
        _rev = self.attachWebSuitable(_rev)
        _rev = self.attachThumbnail(_rev)

        self._doc['_rev'] = _rev

        
    def deleteIfExists(self, url, revision):
        # determine if url (doc or attachment) exists 
        _r = self.headWithRetries(url)
        _missing = _r.status_code == 404
        if not _missing:
            _r = self.deleteWithRetries(url)
            _rev = json.loads(_r.content)['rev']
            return _rev
        else:
            return revision

            
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

        _rev = self._doc['_rev']
        
        # determine if attachments exists ('cause they should be deleted before replaced)
        _rev = self.deleteIfExists(self.imageUrl(_rev), _rev)
        _rev = self.deleteIfExists(self.webSuitableUrl(_rev), _rev)
        _rev = self.deleteIfExists(self.thumbnailUrl(_rev), _rev)

        _rev = self.attachImage(_rev)
        _rev = self.attachWebSuitable(_rev)
        _rev = self.attachThumbnail(_rev)
        
        return self.putJsonWithRetries(self._doc_url, self._doc, _headers)
    

    def downloadDoc(self, url):
        _headers = {"Content-Type": "application/json"}
        _r = self.getWithRetries(url, headers=_headers)
        return json.loads(_r.content) if _r.status_code == 200 else None
    

    def docExists(self):
        return not self.downloadDoc(self._doc_url) is None


    
if __name__ == "__main__":
    import sys
    import argparse
    
    from argparse import RawTextHelpFormatter
    
    _description = 'uploader.py photo uploader'
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
    if _u.recognize():
        _u.updateEntry() if _u.docExists() else _u.createEntry()
    else:
        print("WARNING(%s:%s): skipping unrecognized (invalid) file: %s" %
              (__name__, nowstr(), _args.pic))
        
    
