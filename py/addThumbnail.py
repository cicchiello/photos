#!/usr/bin/python3

import os
import time
import datetime
import hashlib
import json
import requests
import subprocess

from os.path import exists
from requests.auth import HTTPBasicAuth



def nowstr():
    return datetime.datetime.today().strftime('%Y-%b-%d %H:%M:%S')


def md5(fname):
    hash_md5 = hashlib.md5()
    with open(fname, "rb") as f:
        for chunk in iter(lambda: f.read(4096), b""):
            hash_md5.update(chunk)
    return hash_md5.hexdigest()


class ThumbnailAttacher():
    def __init__(self, db, path, creds, verbose=False):
        self._verbose = verbose
        self._doc_id = md5(path)
        self._doc_url = "%s/%s" % (db, self._doc_id)
        self._creds = creds
        self._path = path


    def getDocurl(self):
        return self._doc_url

    
    def getDocid(self):
        return self._doc_id

    
    def attachThumbnail(self, base_path, revision):
        # > convert <given-file> -resize 128 <thumbnail-file>
        _thumbnail_path = "/tmp/thumb_%s" % os.path.basename(base_path)
        
        if self._verbose:
            print("DEBUG(%s:%s): creating thumbnail image: %s" % (__name__, nowstr(), _thumbnail_path))
            
        subprocess.call(["convert", base_path, "-resize", "128", _thumbnail_path])
        _thumbnail_attachmentUrl = "%s/thumbnail?rev=%s" % (self._doc_url, revision)
        _thumbnail_attachmentHeaders = {"Content-Type": "image/%s" % base_path.split(".")[-1]}

        time.sleep(0.5)
        
        if self._verbose:
            print("DEBUG(%s:%s): attaching thumbnail to document(%s)" % (__name__, nowstr(), self._doc_id))
        _thumbnail_attachmentData = open(_thumbnail_path, 'rb').read()
        _r = requests.put(_thumbnail_attachmentUrl, data=_thumbnail_attachmentData,
                          headers=_thumbnail_attachmentHeaders)
        time.sleep(0.5)
        return json.loads(_r.content)["rev"]

    
    def downloadDoc(self, url):
        _headers = {"Content-Type": "application/json"}
        _r = requests.get(url, headers=_headers)
        return json.loads(_r.content) if _r.status_code == 200 else None
    

    def docExists(self):
        return not self.downloadDoc(self._doc_url) is None

    
    def docHasThumbnail(self):
        _attachments = self.downloadDoc(self._doc_url)['_attachments']
        if _attachments is not None:
            return "thumbnail" in _attachments
        else:
            return False

        
    
if __name__ == "__main__":
    import sys
    import argparse
    
    from argparse import RawTextHelpFormatter
    
    _description = 'addThumbnail.py thumbnail creater and attacher'
    _epilog = '\n\nThis creates a thumbnail image and attaches it to the appropriate document\n\n'
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

    _u = ThumbnailAttacher(_args.db, _args.pic, _args.creds.split(":"), verbose=_args.verbose)
    if _u.docExists():
        if not _u.docHasThumbnail():
            if _args.verbose:
                print("DEBUG(%s:%s): updating CouchDb document(%s)" % (__name__, nowstr(), _u.getDocid()))
            
            _doc = _u.downloadDoc(_u.getDocurl())
            _rev = _u.attachThumbnail(_args.pic, _doc['_rev'])
        else:    
            print("INFO(%s:%s): document already has a thumbnail" % (__name__, nowstr()))
    else:
        print("ERROR(%s:%s): document doesn't exist: %s" % (__name__, nowstr(), _u.getDocid()))
    
