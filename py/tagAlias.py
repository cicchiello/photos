#!/usr/bin/python3

import os
import time
import datetime
import hashlib
import json
import requests
import calendar

from os.path import exists
from requests.auth import HTTPBasicAuth


def nowstr():
    return datetime.datetime.today().strftime('%Y-%b-%d %H:%M:%S')


def getWithRetries(url, headers, auth=None):
    _tries = 0
    _sleep = 0.5
    while _tries < 5:
        try:
            return requests.get(url, headers=headers, auth=auth)
        except Exception as e:
            print("WARNING(%s:%s): getWithRetries; caught exception: %s" %
                  (__name__, nowstr(), str(e)))
            _tries += 1
            time.sleep(_sleep)
            _sleep *= 2

    print("ERROR(%s:%s): getWithRetries; quitting after 5 tries" % (__name__, nowstr()))
    exit(-1)


class AllDocsView():
    def __init__(self, db, tag, verbose=False):
        self._tag = tag
        self._db = db
        self._verbose = verbose
        self._baseurl = "%s/_design/photos_by_tag/_search/photos_by_tag" % db

    def getBatch(self, limit, bookmark=None):
        _headers = {"Content-Type": "application/json"}
        _url = "%s?limit=%d&q=%s" % (self._baseurl, limit, self._tag)
        if bookmark:
            _url += "&bookmark=" + bookmark

        if self._verbose:
            print("DEBUG(%s:%s): fetching batch with URL: %s" % (__name__, nowstr(), _url))

        _r = getWithRetries(_url, _headers)
        return json.loads(_r.content) if _r.status_code == 200 else None

    def getAllIds(self):
        _allIds = []
        _bookmark = None
        _done = False
        _Limit = 100

        while not _done:
            _result = self.getBatch(_Limit, _bookmark)
            if not _result:
                break

            _allIds.extend([_row['id'] for _row in _result['rows']])

            if len(_result['rows']) < _Limit or 'bookmark' not in _result:
                _done = True
            else:
                _bookmark = _result['bookmark']

            if self._verbose:
                print("DEBUG(%s:%s): fetched %d documents, total so far: %d" % 
                      (__name__, nowstr(), len(_result['rows']), len(_allIds)))
        
        return _allIds


class ImageDoc():
    def __init__(self, db, id, creds, verbose=False):
        self._verbose = verbose
        self._doc_id = id
        self._doc_url = "%s/%s" % (db, self._doc_id)
        self._creds = creds
        self._doc = None
        self._auth = HTTPBasicAuth(creds[0], creds[1])

    def getDocurl(self):
        return self._doc_url
    
    def getDocid(self):
        return self._doc_id

    def getDoc(self):
        return self._doc

    def downloadDoc(self):
        _headers = {"Content-Type": "application/json"}
        _r = getWithRetries(self._doc_url, _headers, auth=self._auth)
        self._doc = json.loads(_r.content) if _r.status_code == 200 else None
        return self

    def docExists(self):
        return not self.downloadDoc().getDoc() is None


    def getTag(self, doc, tag):
        for _tag in doc['tags']:
            if _tag['Name'] == tag:
                return _tag
        return None


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


    def addAlias(self, doc, alias, username):
        if not self.getTag(doc, alias):
            if self._verbose:
                print("INFO(%s:%s): adding alias tag %s to document %s" %
                      (__name__, nowstr(), alias, self._doc_id))

            _newTag = {}
            _newTag['Name'] = alias
            _newTag['Confidence'] = 100.0
            _newTag['timestamp'] = calendar.timegm(time.gmtime())
            _newTag['source'] = 'user'
            _newTag['username'] = username
            _newTag['Instances'] = []
            _newTag['Parents'] = []
            _newTag['Aliases'] = []
            _newTag['Categories'] = []
            doc['tags'].append(_newTag)

            _headers = {"Content-Type": "application/json"}
            _updateUrl = "%s?rev=%s" % (self._doc_url, doc['_rev'])
            _r = self.putJsonWithRetries(_updateUrl, doc, _headers)
            if _r.status_code != 201:
                print("ERROR(%s:%s): failed to update document %s: %s" % 
                      (__name__, nowstr(), self._doc_id, _r.content))
                return None
            return json.loads(_r.content)["rev"]
        else:
            if self._verbose:
                print("INFO(%s:%s): document %s already has alias tag %s" %
                      (__name__, nowstr(), self._doc_id, alias))
            return doc['_rev']


if __name__ == "__main__":
    import sys
    import argparse
    
    from argparse import RawTextHelpFormatter
    
    _description = 'tagAlias.py finds all occurances of -tag and adds a new -alias tag to the image'
    _epilog = '\n\nFor all images found with tag, it adds a the new alias tag if it doesn\'t already have it'
    _parser = argparse.ArgumentParser(prog=sys.argv[0], description=_description, \
                                      epilog=_epilog, formatter_class=RawTextHelpFormatter)

    _parser.add_argument('-db', nargs='?', required=True, help='path to CouchDb db')
    _parser.add_argument('-creds', nargs='?', required=True, help='CouchDb db credentials (user:pswd)')
    _parser.add_argument('-tag', nargs='?', required=True, help='search tag')
    _parser.add_argument('-alias', nargs='?', required=True, help='tag to add to any images found with -tag')
    _parser.add_argument('-verbose', default=False, action='store_true', help='provide extra debug output')
    
    _args = _parser.parse_args(args=sys.argv[1:])

    print("ECHO(%s:%s): db: %s" % (__name__, nowstr(), _args.db))
    print("ECHO(%s:%s): tag: %s" % (__name__, nowstr(), _args.tag))
    print("ECHO(%s:%s): alias: %s" % (__name__, nowstr(), _args.alias))
    print("ECHO(%s:%s): verbose: %s" % (__name__, nowstr(), _args.verbose))

    _allIds = AllDocsView(_args.db, _args.tag, verbose=_args.verbose).getAllIds()

    print("INFO(%s:%s): Found %d images with tag '%s'" % (__name__, nowstr(), len(_allIds), _args.tag))
    print("INFO(%s:%s): Adding alias tag '%s'..." % (__name__, nowstr(), _args.alias))

    _cnt = 0
    _tenth = 1
    _aliasAddedCount = 0

    for _id in _allIds:
        _imageDoc = ImageDoc(_args.db, _id, _args.creds.split(":"), verbose=_args.verbose).downloadDoc()
        _doc = _imageDoc.getDoc()
        
        print("TRACE(%s:%s): got document" % (__name__, nowstr()))
        if _doc:
            print("TRACE(%s:%s): about to add alias" % (__name__, nowstr()))
            _rev = _imageDoc.addAlias(_doc, _args.alias, _imageDoc.getTag(_doc, _args.tag)['username'])
            print("TRACE(%s:%s): added; _rev: %s" % (__name__, nowstr(), _rev))
            if _rev:
                _aliasAddedCount += 1

        _cnt += 1
        if ((_cnt-1 < _tenth*len(_allIds)/10) and (_cnt >= _tenth*len(_allIds)/10)):
            print("INFO(%s:%s): Processed %d%% of images..." % (__name__, nowstr(), _tenth*10))
            _tenth += 1

    print("INFO(%s:%s): Added alias tag '%s' to %d images" % (__name__, nowstr(), _args.alias, _aliasAddedCount))
