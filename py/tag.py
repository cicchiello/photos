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


def md5(fname):
    hash_md5 = hashlib.md5()
    with open(fname, "rb") as f:
        for chunk in iter(lambda: f.read(4096), b""):
            hash_md5.update(chunk)
    return hash_md5.hexdigest()


class Doc():
    def __init__(self, db, id, creds, verbose=False):
        self._verbose = verbose
        self._doc_id = id
        self._doc_url = "%s/%s" % (db, self._doc_id)
        self._creds = creds


    def getDocurl(self):
        return self._doc_url

    
    def getDocid(self):
        return self._doc_id


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
            
            
    def addTag(self, doc, user, tag):
        _foundTag = False
        for _tag in doc['tags']:
            if (_tag['source'] != 'path-tokenize') and (_tag['Name'] == tag):
                _foundTag = True

        if not _foundTag:
            if self._verbose:
                print("INFO(%s:%s): adding tag %s to document %s" %
                      (__name__, nowstr(), tag, self._doc_id))
                print("INFO(%s:%s): using revision: %s" % (__name__, nowstr(), doc['_rev']))

            _newTag = {}
            _newTag['Name'] = tag
            _newTag['Confidence'] = 99.9
            _newTag['timestamp'] = calendar.timegm(time.gmtime())
            _newTag['source'] = 'user'
            _newTag['username'] = user
            _newTag['Instances'] = []
            _newTag['Parents'] = []
            _newTag['Aliases'] = []
            _newTag['Categories'] = []
            doc['tags'].append(_newTag);
        
            _updateUrl = "%s?rev=%s" % (self._doc_url, doc['_rev'])
            _headers = {"Content-Type": "application/json"}
            _r = self.putJsonWithRetries(_updateUrl, doc, _headers)
            return json.loads(_r.content)["rev"]
        else:
            if self._verbose:
                print("INFO(%s:%s): document %s is already tagged with %s" %
                      (__name__, nowstr(), self._doc_id, tag))
                
            return doc['_rev']

    
    def downloadDoc(self, url):
        _headers = {"Content-Type": "application/json"}
        _r = self.getWithRetries(url, _headers)
        return json.loads(_r.content) if _r.status_code == 200 else None
    

    def docExists(self):
        return not self.downloadDoc(self._doc_url) is None

    
        
    
if __name__ == "__main__":
    import sys
    import argparse
    
    from argparse import RawTextHelpFormatter
    
    _description = 'tag.py adds the specified tag (as a user-tag) to the specified image'
    _epilog = '\n\nThis updates the specified document by adding a tag\n\n'
    _parser = argparse.ArgumentParser(prog=sys.argv[0], description=_description, \
                                      epilog=_epilog, formatter_class=RawTextHelpFormatter)

    _parser.add_argument('-db', nargs='?', required=True, help='path to CouchDb db')
    _parser.add_argument('-creds', nargs='?', required=True, help='CouchDb db credentials (user:pswd)')
    _parser.add_argument('-id', nargs='?', required=True, help='id of the image to tag')
    _parser.add_argument('-user', nargs='?', required=True, help='username to associate with the tag');
    _parser.add_argument('-tag', nargs='?', required=True, help='tag name to associate with the image');
    _parser.add_argument('-verbose', default=False, action='store_true', help='provide extra debug output')
    
    _args = _parser.parse_args(args=sys.argv[1:])

    print("ECHO(%s:%s): db: %s" % (__name__, nowstr(), _args.db))
    print("ECHO(%s:%s): id: %s" % (__name__, nowstr(), _args.id))
    print("ECHO(%s:%s): user: %s" % (__name__, nowstr(), _args.user))
    print("ECHO(%s:%s): tag: %s" % (__name__, nowstr(), _args.tag))
    print("ECHO(%s:%s): verbose: %s" % (__name__, nowstr(), _args.verbose))

    _u = Doc(_args.db, _args.id, _args.creds.split(":"), verbose=_args.verbose)
    if _u.docExists():
        _rev = _u.addTag(_u.downloadDoc(_u.getDocurl()), _args.user, _args.tag)
        if _args.verbose:
            print("INFO(%s:%s): new revision: %s" % (__name__, nowstr(), _rev))
    else:
        print("ERROR(%s:%s): document doesn't exist: %s" % (__name__, nowstr(), _u.getDocid()))
    
