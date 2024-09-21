#!/usr/bin/python

import sys
import calendar
import time
import datetime
import argparse
import hashlib
import json
import requests
from os.path import exists

from requests.auth import HTTPBasicAuth
from argparse import RawTextHelpFormatter

from lucenize import Lucenize


def nowstr():
    return datetime.datetime.today().strftime('%Y-%b-%d %H:%M:%S')


def md5(fname):
    hash_md5 = hashlib.md5()
    with open(fname, "rb") as f:
        for chunk in iter(lambda: f.read(4096), b""):
            hash_md5.update(chunk)
    return hash_md5.hexdigest()


class Uploader():
    def __init__(self, db, path, user_keywords, creds):
        self._doc_url = "%s/%s" % (db, md5(path))
        self._creds = creds
        self._doc = {}
        self._doc['paths'] = [path]
        self._doc['content_type'] = "image/%s" % path.split(".")[-1]
        self._doc['metadata_keywords'] = Lucenize().keywords(path)
        self._doc['upload_timestamp'] = calendar.timegm(time.gmtime())
        self._doc['user_keywords'] = user_keywords

        
    def createEntry(self):
        _headers = {"Content-Type": "application/json"}
        _r = requests.put(self._doc_url, json=self._doc, headers=_headers)
        
        if b'conflict' in _r.content:
            print("ERROR(%s:%s): conflict" % (__name__, nowstr()))
            exit()

        _rev = json.loads(_r.content)["rev"]
        _attachmentUrl = "%s/image?rev=%s" % (self._doc_url, _rev)
        _attachmentHeaders = {"Content-Type": "image/%s" % self._doc['paths'][0].split(".")[-1]}
        _attachmentData = open(self._doc['paths'][0], 'rb').read()
        return requests.put(_attachmentUrl, data=_attachmentData, headers=_attachmentHeaders)
    
    
    def updateEntry(self):
        _doc = self.downloadDoc(self._doc_url)
        _doc['paths'] = list(set(_doc['paths']).union(self._doc['paths']))
        _doc['metadata_keywords'] = list(set(_doc['metadata_keywords']).union(Lucenize().keywords(self._doc['paths'][0])))
        _doc['user_keywords'] = list(set(_doc['user_keywords']).union(self._doc['user_keywords']))
        
        del _doc['_id']
        _headers = {"Content-Type": "application/json"}
        return requests.put(self._doc_url, json=_doc, headers=_headers)
    

    def downloadDoc(self, url):
        _headers = {"Content-Type": "application/json"}
        _r = requests.get(url, headers=_headers)
        return json.loads(_r.content) if _r.status_code == 200 else None
    

    def docExists(self):
        return not self.downloadDoc(self._doc_url) is None


    
if __name__ == "__main__":
    _description = 'upload_pics.py photo uploader'
    _epilog = '\n\nThis uploads the given photo and metadata\n\n'
    _parser = argparse.ArgumentParser(prog=sys.argv[0], description=_description, \
                                      epilog=_epilog, formatter_class=RawTextHelpFormatter)

    _parser.add_argument('-db', nargs='?', required=True, help='path to CouchDb db')
    _parser.add_argument('-creds', nargs='?', required=True, help='CouchDb db credentials (user:pswd)')
    _parser.add_argument('-pic', nargs='?', required=True, help='path to image file')
    _parser.add_argument('-keywords', nargs='*', action='append', required=False, \
                         help='space-separated keywords for image')
    
    _args = _parser.parse_args(args=sys.argv[1:])

    if not exists(_args.pic):
        print("\nERROR(%s:%s): %s not found\n" % (__name__, nowstr(), _args.pic))
        _parser.print_help()
        exit()
    
    print("ECHO(%s:%s): db: %s" % (__name__, nowstr(), _args.db))
    print("ECHO(%s:%s): pic: %s" % (__name__, nowstr(), _args.pic))

    _tags = _args.keywords[0] if not _args.keywords is None else []
    _u = Uploader(_args.db, _args.pic, _tags, _args.creds.split(":"))
    _r = _u.updateEntry() if _u.docExists() else _u.createEntry()
    
