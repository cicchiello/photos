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


def usage():
    print("Usage: %s" % (sys.argv[0]))
    print("")
    exit()

def nowstr():
    return datetime.datetime.today().strftime('%Y-%b-%d %H:%M:%S')

def md5(fname):
    hash_md5 = hashlib.md5()
    with open(fname, "rb") as f:
        for chunk in iter(lambda: f.read(4096), b""):
            hash_md5.update(chunk)
    return hash_md5.hexdigest()

def createDoc(args):
    _doc = {}
    _doc['path'] = args.pic
    _doc['path-keywords'] = Lucenize().keywords(args.pic)
    _doc['upload-timestamp'] = calendar.timegm(time.gmtime())
    _doc['user-keywords'] = []
    if not args.keywords is None: 
        _doc['user-keywords'] = args.keywords[0]
    return _doc

def uploadDoc(doc, url, creds):
    print("DEBUG(%s:%s): user: %s" % (__name__, nowstr(), creds[0]))
    print("DEBUG(%s:%s): pswd: %s" % (__name__, nowstr(), creds[1]))
    #_session = requests.Session()
    #_session.auth = (creds[0], creds[1])
    #_auth=HTTPBasicAuth(creds[0], creds[1])
    _headers = {"Content-Type": "application/json"}
    #_r = requests.put(url, json=doc, auth=_auth, headers=_headers)
    return requests.put(url, json=doc, headers=_headers)

def uploadPic(picFilename, attachmentUrl, creds):
    _attachmentHeaders = {"Content-Type": "image/%s" % picFilename.split(".")[-1]}
    #_r = requests.put(url, json=doc, auth=_auth, headers=_headers)
    _attachmentData = open(picFilename, 'rb').read()
    return requests.put(attachmentUrl, data=_attachmentData, headers=_attachmentHeaders)



if __name__ == "__main__":
    _description = 'upload_pics.py photo uploader'
    _epilog = '\n\nThis uploads the given photo and metadata\n\n'
    _parser = argparse.ArgumentParser(prog=sys.argv[0], description=_description, \
                                      epilog=_epilog, formatter_class=RawTextHelpFormatter)

    _parser.add_argument('-db', nargs='?', required=True, help='path to CouchDb db')
    _parser.add_argument('-creds', nargs='?', required=True, help='CouchDb db credentials (user:pswd)')
    _parser.add_argument('-pic', nargs='?', required=True, help='path to image file')
    _parser.add_argument('-keywords', nargs='*', action='append', required=False, help='space-separated keywords for image')
    
    _args = _parser.parse_args(args=sys.argv[1:])

    if not exists(_args.pic):
        print("\nERROR(%s:%s): %s not found\n" % (__name__, nowstr(), _args.pic))
        _parser.print_help()
        exit()
    
    print("ECHO(%s:%s): db: %s" % (__name__, nowstr(), _args.db))
    print("ECHO(%s:%s): pic: %s" % (__name__, nowstr(), _args.pic))

    _doc = createDoc(_args)
    print("DEBUG(%s:%s): doc: %s" % (__name__, nowstr(), _doc))
    
    _url = "%s/%s" % (_args.db, md5(_args.pic))
    _creds = _args.creds.split(":")
    _r = uploadDoc(_doc, _url, _creds)
    
    print("DEBUG(%s:%s): _r.status_code: %s" % (__name__, nowstr(), _r.status_code))
    print("DEBUG(%s:%s): _r.content: %s" % (__name__, nowstr(), _r.content))

    _attachmentUrl = "%s/%s/%s?rev=%s" % (_args.db, md5(_args.pic), _args.pic.split(".")[-1], json.loads(_r.content)["rev"])
    _r = uploadPic(_args.pic, _attachmentUrl, _creds)
    
    print("DEBUG(%s:%s): _r.status_code: %s" % (__name__, nowstr(), _r.status_code))
    print("DEBUG(%s:%s): _r.content: %s" % (__name__, nowstr(), _r.content))
    
