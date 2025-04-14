#!/usr/bin/python3

import os
import time
import datetime
import json
import requests
import subprocess
import boto3
import urllib.request

from os.path import exists
from requests.auth import HTTPBasicAuth



def nowstr():
    return datetime.datetime.today().strftime('%Y-%b-%d %H:%M:%S')



def listAllJsonFiles(dir):
    _r = []
    _directory = os.fsencode(dir)
    for _file in os.listdir(_directory):
        _filename = os.fsdecode(_file)
        if _filename.endswith(".json"):
            _r.append(os.path.join(dir, _filename))
        elif os.path.isdir(os.path.join(dir, _filename)):
            _r.extend(listAllJsonFiles(os.path.join(dir, _filename)))

    return _r



def putBinWithRetries(url, data, headers, auth=None):
    _tries = 0
    _sleep = 0.5
    while _tries < 5:
        try:
            return requests.put(url, data=data, headers=headers, auth=auth)
        except Exception as e:
            print("WARNING(%s:%s): putBinWithRetries; caught exception: %s" %
                  (__name__, nowstr(), str(e)))
            _tries += 1
            time.sleep(_sleep)
            _sleep *= 2
                
    print("ERROR(%s:%s): putBinWithRetries; quitting after 5 tries; url: %s" % (__name__, nowstr(), url))
    exit(-1)
        
            

def putJsonWithRetries(url, jdoc, auth=None):
    _tries = 0
    _sleep = 0.5
    _headers = {"Content-Type": "application/json"}
    while _tries < 5:
        try:
            _r = requests.put(url, json=jdoc, headers=_headers, auth=auth)
            return _r
        except Exception as e:
            print("WARNING(%s:%s): putJsonWithRetries; caught exception: %s" %
                  (__name__, nowstr(), str(e)))
            _tries += 1
            time.sleep(_sleep)
            _sleep *= 2
                
    print("ERROR(%s:%s): putJsonWithRetries; quitting after 5 tries; url" % (__name__, nowstr(), url))
    exit(-1)

        

def attUrl(db, id, attName, rev):
    return db+"/"+id+"/"+attName+"?rev="+rev


def attFilename(dir, id, attName):
    return dir+"/"+id+"_"+attName
    

class AllDocsView():
    def __init__(self, db, creds, verbose=False):
        self._creds = creds
        self._baseurl = "%s/_all_docs" % db
        #like: "http://HOST:5984/photos/_all_docs?skip="+offset

    def getBatch(self, limit, offset):
        _headers = {"Content-Type": "application/json"}
        _url = "%s?limit=%d&skip=%d" % (self._baseurl, limit, offset)
        _r = requests.get(_url, headers=_headers)
        return json.loads(_r.content) if _r.status_code == 200 else None

    def getAllIds(self):
        _allIds = []
        _offset = 0
        _done = False
        _Limit = 100
        while not _done:
            _rows = self.getBatch(_Limit, _offset)['rows']
            _allIds.extend([_row['id'] for _row in _rows])
            _offset += len(_rows)
            _done = len(_rows) < _Limit
            #print("DEBUG(%s:%s): got %d" % (__name__, nowstr(), len(_rows)))
        
        return _allIds


    
class Doc():
    def __init__(self, db, id, creds, verbose=False):
        self._verbose = verbose
        self._doc_id = id
        self._doc_url = "%s/%s" % (db, self._doc_id)
        self._creds = creds
        self._doc = None

    def getDocurl(self):
        return self._doc_url
    
    def getDocid(self):
        return self._doc_id

    def getDoc(self):
        return self._doc

    def downloadDoc(self):
        _headers = {"Content-Type": "application/json"}

        _retry = 0
        while _retry < 3:
            try: 
                _r = requests.get(self._doc_url, headers=_headers)
                self._doc = json.loads(_r.content) if _r.status_code == 200 else None
                return self
            except Exception as e:
                print('WARNING(%s:%s): Exception trap: %s' % (__name__, nowstr(), str(e)))
                _retry += 1
                time.sleep(0.5)

        print('ERROR(%s:%s): Exception trap: %s' % (__name__, nowstr(), self._doc_url))
        exit(-1)


    
if __name__ == "__main__":
    import sys
    import argparse
    
    from argparse import RawTextHelpFormatter
    
    _description = 'restore.py uploads all json documents and attachments found in given directory to the specified db'
    _epilog = '\n\nIt uploads all docs and attachments from the filesystem the specified CouchDb db\n'
    _parser = argparse.ArgumentParser(prog=sys.argv[0], description=_description, \
                                      epilog=_epilog, formatter_class=RawTextHelpFormatter)

    _parser.add_argument('-db', nargs='?', required=True, help='path to CouchDb db')
    _parser.add_argument('-creds', nargs='?', required=True, help='CouchDb db credentials (user:pswd)')
    _parser.add_argument('-dir', nargs='?', required=True, help='directory to place the db content')
    _parser.add_argument('-verbose', default=False, action='store_true', help='provide extra debug output')
    
    _args = _parser.parse_args(args=sys.argv[1:])

    print("ECHO(%s:%s): db: %s" % (__name__, nowstr(), _args.db))
    print("ECHO(%s:%s): dir: %s" % (__name__, nowstr(), _args.dir))
    print("ECHO(%s:%s): verbose: %s" % (__name__, nowstr(), _args.verbose))

    _creds = _args.creds.split(":")
    _auth = HTTPBasicAuth(_creds[0], _creds[1])
    
    _tenth = 1
    _stats = {
        'total_found': 0,
        'uploaded_docs': 0,
        'uploaded_attachments': 0,
        'failed_docs': 0,
        'failed_attachments': 0
    }

    _allJsonFiles = listAllJsonFiles(_args.dir)
    for _filename in _allJsonFiles:

        with open(_filename) as f:
            _doc = json.load(f)

        # Remove rev (if it's there), and attachment stubs before upload
        _doc.pop('_rev', None)
        _attachments = _doc.pop('_attachments', None)
        
        _docUrl = _args.db+"/"+_doc['_id']
        if _args.verbose:
            print("DEBUG(%s:%s): docUrl: %s" % (__name__, nowstr(), _docUrl))
            
        _r = putJsonWithRetries(_docUrl, _doc, auth=_auth)
        if b'error' in _r.content:
            print("ERROR(%s:%s): PUT error; url: %s" % (__name__, nowstr(), _docUrl))
            print("ERROR(%s:%s): (continued) content: %s" % (__name__, nowstr(), str(json.loads(_r.content))))
            _stats['failed_docs'] += 1
            exit(-1)
            continue

        _rev = json.loads(_r.content)["rev"]
        _stats['uploaded_docs'] += 1
        
        if _attachments is not None:
            for _attName, _obj in _attachments.items():
                if not os.path.exists(attFilename(_args.dir, _doc['_id'], _attName)):
                    print("WARNING(%s:%s): attachment file not found: %s" %
                          (__name__, nowstr(), attFilename(_args.dir, _doc['_id'], _attName)))
                    _stats['failed_attachments'] += 1
                    continue

                try:
                    _attHeaders = {"Content-Type": "%s" % (_obj['content_type'])}
                    _url = attUrl(_args.db, _doc['_id'], _attName, _rev)
                    _r = putBinWithRetries(_url,
                                           open(attFilename(_args.dir, _doc['_id'], _attName), 'rb').read(),
                                           _attHeaders, auth=_auth)
                    if b'error' in _r.content:
                        print("ERROR(%s:%s): PUT error; url: %s" % (__name__, nowstr(), _url))
                        print("ERROR(%s:%s): (continued) content: %s" % (__name__, nowstr(), str(json.loads(_r.content))))
                        _stats['failed_attachments'] += 1
                        continue

                    _rev = json.loads(_r.content)["rev"]
                    _stats['uploaded_attachments'] += 1
                except Exception as e:
                    print("ERROR(%s:%s): Failed to upload attachment: %s" % (__name__, nowstr(), str(e)))
                    _stats['failed_attachments'] += 1
                
        # report progress
        if (_stats['total_found'] <= _tenth*len(_allJsonFiles)/10.0) and (_stats['total_found']+1 > _tenth*len(_allJsonFiles)/10.0):
            print("INFO(%s:%s): %d%% complete..." % (__name__, nowstr(), _tenth*10))
            _tenth += 1
            
        _stats['total_found'] += 1

        
            
    # Print statistics at the end
    print("")
    print("STATS(%s:%s): Processing Results" % (__name__, nowstr()))
    print("STATS(%s:%s): ------------------" % (__name__, nowstr()))
    print("STATS(%s:%s): Total docs found: %d" % (__name__, nowstr(), _stats['total_found']))
    print("STATS(%s:%s): Total docs uploaded: %d" % (__name__, nowstr(), _stats['uploaded_docs']))
    print("STATS(%s:%s): Total docs failed: %d" % (__name__, nowstr(), _stats['failed_docs']))
    print("STATS(%s:%s): Total attachments uploaded: %d" % (__name__, nowstr(), _stats['uploaded_attachments']))
    print("STATS(%s:%s): Total attachments failed: %d" % (__name__, nowstr(), _stats['failed_attachments']))
    print("")
