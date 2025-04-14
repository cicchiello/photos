#!/usr/bin/python3

import os
import time
import datetime
import json
import requests
import subprocess
import boto3
import urllib.request

from os.path import exists, isdir
from requests.auth import HTTPBasicAuth



def nowstr():
    return datetime.datetime.today().strftime('%Y-%b-%d %H:%M:%S')



class AllDocsView():
    def __init__(self, db, creds, verbose=False):
        self._creds = creds
        self._baseurl = "%s/_all_docs" % db
        self._verbose = verbose
        #like: "http://HOST:5984/photos/_all_docs?skip="+offset

    def getBatch(self, limit, offset):
        _headers = {"Content-Type": "application/json"}
        _url = "%s?limit=%d&skip=%d" % (self._baseurl, limit, offset)
        _r = requests.get(_url, headers=_headers)
        return json.loads(_r.content) if _r.status_code == 200 else None

    def getAllIds(self):
        print("INFO(%s:%s): getting all ids" % (__name__, nowstr()))
        _allIds = []
        _offset = 0
        _done = False
        _Limit = 100
        while not _done:
            _rows = self.getBatch(_Limit, _offset)['rows']
            _allIds.extend([_row['id'] for _row in _rows])
            _offset += len(_rows)
            _done = len(_rows) < _Limit
            if len(_allIds) % 1000 == 0:
                print("INFO(%s:%s): got %d ids" % (__name__, nowstr(), len(_allIds)))
            elif self._verbose:
                print("DEBUG(%s:%s): got %d ids" % (__name__, nowstr(), len(_allIds)))
        
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

        print('ERROR(%s:%s): Exception trap: %s' % (__name__, nowstr()))
        exit(-1)


    
if __name__ == "__main__":
    import sys
    import argparse
    
    from argparse import RawTextHelpFormatter
    
    _description = 'backup.py dumps the entire contents of a CouchDb db the filesystem'
    _epilog = '\n\nIt retrieves all docs and attachments from the db and writes them to the specified directory\n'
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

    print("INFO(%s:%s):" % (__name__, nowstr()))
    _allIds = AllDocsView(_args.db, _args.creds.split(":"), verbose=_args.verbose).getAllIds()
    print("INFO(%s:%s):" % (__name__, nowstr()))
    print("INFO(%s:%s): Processing %d docs" % (__name__, nowstr(), len(_allIds)-1))

    _tenth = 1
    _stats = {
        'total_processed': 0,
        'downloaded_docs': 0,
        'downloaded_attachments': 0,
        'failed_docs': 0,
        'failed_attachments': 0
    }

    for _id in _allIds:
        _docObj = Doc(_args.db, _id, _args.creds.split(":"), verbose=_args.verbose).downloadDoc()
        _doc = _docObj.getDoc()

        if "_design" in _id:
            if os.path.isdir(_args.dir+"/_design"):
                pass
            else:
                os.mkdir(_args.dir+"/_design")

        _filename = _args.dir+"/"+_id+".json"
        with open(_filename, 'w', encoding='utf-8') as f:
            json.dump(_doc, f, ensure_ascii=False, indent=4)
            
        _stats['downloaded_docs'] += 1

        if '_attachments' in _doc:
            for _attachmentName, _obj in _doc['_attachments'].items():
                _attachmentFilename = _args.dir+"/"+_id+"_"+_attachmentName
                _attachmentUrl = _docObj.getDocurl()+"/"+_attachmentName
                urllib.request.urlretrieve(_attachmentUrl, _attachmentFilename)
                _stats['downloaded_attachments'] += 1

        # report progress
        if (_stats['total_processed'] <= _tenth*len(_allIds)/10.0) and (_stats['total_processed']+1 > _tenth*len(_allIds)/10.0):
            print("INFO(%s:%s): %d%% complete..." % (__name__, nowstr(), _tenth*10))
            _tenth += 1
                
        _stats['total_processed'] += 1

    # Print statistics at the end
    print("")
    print("STATS(%s:%s): Processing Results" % (__name__, nowstr()))
    print("STATS(%s:%s): ------------------" % (__name__, nowstr()))
    print("STATS(%s:%s): Total docs processed: %d" % (__name__, nowstr(), _stats['total_processed']))
    print("STATS(%s:%s): Total docs saved: %d" % (__name__, nowstr(), _stats['downloaded_docs']))
    print("STATS(%s:%s): Total attachments saved: %d" % (__name__, nowstr(), _stats['downloaded_attachments']))
    print("")
