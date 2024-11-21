#!/usr/bin/python3

import os
import time
import datetime
import hashlib
import json
import requests

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


class ImageDoc():
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


    def addIdx(self, doc, idx):
        if self._verbose:
            print("DEBUG(%s:%s): adding idx(%d) field to document: %s" %
                  (__name__, nowstr(), idx,self._doc_id))
            print("DEBUG(%s:%s): using revision: %s" % (__name__, nowstr(), doc['_rev']))

        doc['idx'] = idx;
        
        _updateUrl = "%s?rev=%s" % (self._doc_url, doc['_rev'])
        _headers = {"Content-Type": "application/json"}
        _r = requests.put(_updateUrl, json=doc, headers=_headers)
        
        time.sleep(0.5)
        return json.loads(_r.content)["rev"]

    
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
    
    _description = 'addIdx.py add an "idx" field to the given document'
    _epilog = '\n\nThis updates the specified document with the provided idx value\n\n'
    _parser = argparse.ArgumentParser(prog=sys.argv[0], description=_description, \
                                      epilog=_epilog, formatter_class=RawTextHelpFormatter)

    _parser.add_argument('-db', nargs='?', required=True, help='path to CouchDb db')
    _parser.add_argument('-creds', nargs='?', required=True, help='CouchDb db credentials (user:pswd)')
    _parser.add_argument('-pic', nargs='?', required=True, help='path to image file')
    _parser.add_argument('-idx', nargs='?', required=True, help='integer idx value')
    _parser.add_argument('-verbose', default=False, action='store_true', help='provide extra debug output')
    
    _args = _parser.parse_args(args=sys.argv[1:])

    if not exists(_args.pic):
        print("\nERROR(%s:%s): %s not found\n" % (__name__, nowstr(), _args.pic))
        _parser.print_help()
        exit()
    
    if not isinstance(int(_args.idx), int):
        print("\nERROR(%s:%s): %s is not an integer\n" % (__name__, nowstr(), _args.idx))
        _parser.print_help()
        exit()
    
    print("ECHO(%s:%s): db: %s" % (__name__, nowstr(), _args.db))
    print("ECHO(%s:%s): pic: %s" % (__name__, nowstr(), _args.pic))
    print("ECHO(%s:%s): idx: %s" % (__name__, nowstr(), _args.idx))
    print("ECHO(%s:%s): verbose: %s" % (__name__, nowstr(), _args.verbose))

    _u = ImageDoc(_args.db, _args.pic, _args.creds.split(":"), verbose=_args.verbose)
    if _u.docExists():
        _doc = _u.downloadDoc(_u.getDocurl())
        _rev = _u.addIdx(_doc, int(_args.idx))
        if _args.verbose:
            print("INFO(%s:%s): new revision: %s" % (__name__, nowstr(), _rev))
    else:
        print("ERROR(%s:%s): document doesn't exist: %s" % (__name__, nowstr(), _u.getDocid()))
    
