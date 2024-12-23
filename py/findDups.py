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


def getWithRetries(url, headers):
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
            

class AllDocsView():
    def __init__(self, db, creds, verbose=False):
        self._creds = creds
        self._baseurl = "%s/_design/photos/_view/photo_ids?descending=false" % db
        #like: "http://HOST:5984/photos/_design/photos/_view/photo_ids?descending=false&skip="+offset

    def getBatch(self, limit, offset):
        _headers = {"Content-Type": "application/json"}
        _url = "%s&limit=%d&skip=%d" % (self._baseurl, limit, offset)
        _r = getWithRetries(_url, _headers)
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


    
class ImageDoc():
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
        _r = getWithRetries(self._doc_url, _headers)
        self._doc = json.loads(_r.content) if _r.status_code == 200 else None
        return self


    def downloadWebImage(self, path):
        _webimage_attachmentUrl = "%s/web_image" % (self._doc_url)
        subprocess.call(["curl", _webimage_attachmentUrl, "-o", path],
                        stdout=subprocess.DEVNULL,
                        stderr=subprocess.STDOUT)
        if not exists(path):
            print("ERROR(%s:%s): failed to create %s" % (__name__, nowstr(), path))
            exit(-1)
        time.sleep(0.5)
    
    def docExists(self):
        return not self.downloadDoc(self._doc_url).getDoc() is None

    def calcRegistrationTagset(self):
        _registrationTags = []

        for _tag in self._doc['tags']:
            if _tag['source'] == 'rekognition':
                if _tag['Confidence'] > 97.0:
                    _registrationTags.append(_tag['Name'])

        _registrationTags.sort()
        
        self._tagset = ""
        for _tag in _registrationTags:
            if self._tagset != "":
                self._tagset = "%s " % self._tagset
            self._tagset = "%s%s" % (self._tagset, _tag)

        return self._tagset
    

    
if __name__ == "__main__":
    import sys
    import argparse
    
    from argparse import RawTextHelpFormatter
    
    _description = 'findDups.py attempts to find pairs of entries that are duplicates of one another'
    _epilog = '\n\nIt retrieves the confidence tagset from every element, and looks for matches\n'
    _parser = argparse.ArgumentParser(prog=sys.argv[0], description=_description, \
                                      epilog=_epilog, formatter_class=RawTextHelpFormatter)

    _parser.add_argument('-db', nargs='?', required=True, help='path to CouchDb db')
    _parser.add_argument('-creds', nargs='?', required=True, help='CouchDb db credentials (user:pswd)')
    _parser.add_argument('-verbose', default=False, action='store_true', help='provide extra debug output')
    
    _args = _parser.parse_args(args=sys.argv[1:])

    print("ECHO(%s:%s): db: %s" % (__name__, nowstr(), _args.db))
    print("ECHO(%s:%s): verbose: %s" % (__name__, nowstr(), _args.verbose))

    _allIds = AllDocsView(_args.db, _args.creds.split(":"), verbose=_args.verbose).getAllIds()

    print("INFO(%s:%s):" % (__name__, nowstr()))
    print("INFO(%s:%s):" % (__name__, nowstr()))
    print("INFO(%s:%s): Collecting tagsets for %d images..." %
          (__name__, nowstr(), len(_allIds)))
    
    _tagsetDict = {}
    _cnt = 0
    _tenth = 1
    _tagsetMatchCnt = 0
    for _id in _allIds:
        _imageDoc = ImageDoc(_args.db, _id, _args.creds.split(":"), verbose=_args.verbose)
        _tagset = _imageDoc.downloadDoc().calcRegistrationTagset()
        #print("DEBUG(%s:%s): tagset: %s" % (__name__, nowstr(), _tagset))
        
        if (_tagset != "") and (_tagset in _tagsetDict):
            _tagsetDict[_tagset].append(_id);
            if len(_tagsetDict[_tagset]) == 2:
                _tagsetMatchCnt += 1
            if _args.verbose:
                print("DEBUG(%s:%s): found duplicate registrationTagset: %s %s" %
                      (__name__, nowstr(), _tagset, _tagsetDict[_tagset]))
        else:
            _tagsetDict[_tagset] = [_id]

        _cnt += 1
        if ((_cnt-1 < _tenth*len(_allIds)/10) and (_cnt >= _tenth*len(_allIds)/10)):
            print("INFO(%s:%s): collected tagsets from %d%%..." % (__name__, nowstr(), _tenth*10))
            _tenth += 1

            
    print("INFO(%s:%s):" % (__name__, nowstr()))
    print("INFO(%s:%s):" % (__name__, nowstr()))
    print("INFO(%s:%s): Done collecting tagsets; Now considering %d matches based on tagset similarity..." %
          (__name__, nowstr(), _tagsetMatchCnt))

    _firstFilename = None
    _dupCnt = 0
    _tenth = 1
    for _tagset in _tagsetDict:
        if len(_tagsetDict[_tagset]) > 1:
            print("INFO(%s:%s): duplicate candidate: %s" % (__name__, nowstr(), str(_tagsetDict[_tagset])))

            _candidateCnt = 0
            for _id in _tagsetDict[_tagset]:
                _image = ImageDoc(_args.db, _id, _args.creds.split(":"), verbose=_args.verbose)
                _imageDoc = _image.downloadDoc()
                _ext = os.path.basename(_imageDoc.getDoc()['paths'][0]).split(".")[-1]
                _filename = "/tmp/candidate%d_%d.%s" % (_dupCnt, _candidateCnt, _ext)
                if _args.verbose:
                    print("DEBUG(%s:%s): downloading webimage to: %s" %
                          (__name__, nowstr(), _filename))
                _imageDoc.downloadWebImage(_filename)
                _candidateCnt += 1
                if _candidateCnt > 1:
                    if _args.verbose:
                        print("DEBUG(%s:%s): comparing: %s vs %s " %
                              (__name__, nowstr(), _firstFilename, _filename))
                    _ps = subprocess.Popen(['compare', '-metric', 'RMSE', 
                                            _firstFilename, _filename, 'null:'],
                                           stderr=subprocess.PIPE)
                    _s = 's/.*(\\(.*\\))/\\1/g'
                    #print("DEBUG(%s:%s): %s: " % (__name__, nowstr(), _s))
                    _output = subprocess.check_output(['sed', _s], stdin=_ps.stderr)
                    _ps.stderr.close()
                    _ps.wait()
                    _err = float(_output.decode('utf-8').strip())
                    #print("DEBUG(%s:%s): comparison error: %s" %
                    #      (__name__, nowstr(), _output.decode('utf-8').strip()))
                    if _err < 0.05:
                        #print("DEBUG(%s:%s): _imageDoc: %s" %
                        #      (__name__, nowstr(), str(_imageDoc.getDoc())))
                        _s0 = _firstImageDoc.getDoc()['size']
                        _s1 = _imageDoc.getDoc()['size']
                        #print("DEBUG(%s:%s): %d %d" % (__name__, nowstr(), _s0, _s1))
                        _hideId = _firstId if _s0 < _s1 else _id
                        #print("INFO(%s:%s): " % (__name__, nowstr()))
                        #print("INFO(%s:%s): " % (__name__, nowstr()))
                        #print("INFO(%s:%s): %s %s (%f)" % (__name__, nowstr(), _firstId, _id, _err))
                        #print("INFO(%s:%s): " % (__name__, nowstr()))
                        #print("INFO(%s:%s): " % (__name__, nowstr()))

                        if _hideId == _firstId:
                            #print("DEBUG(%s:%s): _firstId(%d): %s" % (__name__, nowstr(), _s0, _firstId))
                            #print("DEBUG(%s:%s): _id(%d): %s" % (__name__, nowstr(), _s1, _id))
                            print("INFO(%s:%s): propose to hide _firstId: %s; keep: %s" %
                                  (__name__, nowstr(), _hideId, _id))
                            os.remove(_firstFilename)
                            _firstId = _id
                            _firstFilename = _filename
                            _firstImageDoc = _imageDoc
                        else:
                            #print("DEBUG(%s:%s): _firstId(%d): %s" % (__name__, nowstr(), _s0, _firstId))
                            #print("DEBUG(%s:%s): _id(%d): %s" % (__name__, nowstr(), _s1, _id))
                            print("INFO(%s:%s): propose to hide _id: %s; keep: %s" %
                                  (__name__, nowstr(), _hideId, _firstId))
                            os.remove(_filename)
                            
                    elif _err < 0.07:
                        # this might be interesting later...
                        print("INFO(%s:%s): close, but not close enough: %s %s (%f)" %
                              (__name__, nowstr(), _firstId, _id, _err))
                        os.remove(_filename)
                        
                    else:
                        os.remove(_filename)

                else:
                    if _firstFilename is not None:
                        # Done with _firstFilename
                        os.remove(_firstFilename)
                        
                    _firstFilename = _filename
                    _firstId = _id
                    _firstImageDoc = _imageDoc

            _dupCnt += 1
            if ((_dupCnt-1 < _tenth*_tagsetMatchCnt/10) and (_dupCnt >= _tenth*_tagsetMatchCnt/10)):
                print("INFO(%s:%s): done considering %d%% of tagset matches..." % (__name__, nowstr(), _tenth*10))
                _tenth += 1

            

