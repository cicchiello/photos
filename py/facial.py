#!/usr/bin/python3

import os
import time
import datetime
import json
import requests
import subprocess
import boto3

from os.path import exists
from requests.auth import HTTPBasicAuth



def nowstr():
    return datetime.datetime.today().strftime('%Y-%b-%d %H:%M:%S')



def compare_faces(sourceFile, targetFile):
    _client = boto3.Session(profile_name='default').client('rekognition')

    _response = None
    try: 
        with open(sourceFile, 'rb') as _sfile:
            with open(targetFile, 'rb') as _tfile:
                _response = _client.compare_faces(SimilarityThreshold=90,
                                                  SourceImage={'Bytes': _sfile.read()},
                                                  TargetImage={'Bytes': _tfile.read()})
    except Exception as _e:
        #print('WARNING(%s:%s): Exception trap: %s' % (__name__, nowstr(), str(_e)))
        # silently fail
        pass
        
    
    if _response is not None: 
        for _faceMatch in _response['FaceMatches']:
            _position = _faceMatch['Face']['BoundingBox']
            _similarity = str(_faceMatch['Similarity'])
            #print('The face at ' +
            #      str(_position['Left']) + ' ' +
            #      str(_position['Top']) +
            #      ' matches with ' + _similarity + '% confidence')
            return _similarity

    return None
    




class AllDocsView():
    def __init__(self, db, creds, verbose=False):
        self._creds = creds
        self._baseurl = "%s/_design/photos/_view/photo_ids?descending=false" % db
        #like: "http://HOST:5984/photos/_design/photos/_view/photo_ids?descending=false&skip="+offset

    def getBatch(self, limit, offset):
        _headers = {"Content-Type": "application/json"}
        _url = "%s&limit=%d&skip=%d" % (self._baseurl, limit, offset)
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
    
    _description = 'facial.py attempts to identify an exemplar face within all the images in the db'
    _epilog = '\n\nIt retrieves all images from the db and tests all of them against the exemplar\n'
    _parser = argparse.ArgumentParser(prog=sys.argv[0], description=_description, \
                                      epilog=_epilog, formatter_class=RawTextHelpFormatter)

    _parser.add_argument('-db', nargs='?', required=True, help='path to CouchDb db')
    _parser.add_argument('-creds', nargs='?', required=True, help='CouchDb db credentials (user:pswd)')
    _parser.add_argument('-verbose', default=False, action='store_true', help='provide extra debug output')
    _parser.add_argument('-exemplarId', nargs='?', required=True, help='id of the example face')
    _parser.add_argument('-tag', nargs='?', required=True, help='tag to check for matching faces')
    
    _args = _parser.parse_args(args=sys.argv[1:])

    print("ECHO(%s:%s): db: %s" % (__name__, nowstr(), _args.db))
    print("ECHO(%s:%s): verbose: %s" % (__name__, nowstr(), _args.verbose))
    print("ECHO(%s:%s): exemplarId: %s" % (__name__, nowstr(), _args.exemplarId))
    print("ECHO(%s:%s): tag: %s" % (__name__, nowstr(), _args.tag))

    _allIds = AllDocsView(_args.db, _args.creds.split(":"), verbose=_args.verbose).getAllIds()
    print("INFO(%s:%s): Processing %d images" % (__name__, nowstr(), len(_allIds)-1))

    _exemplarDoc = ImageDoc(_args.db, _args.exemplarId,
                            _args.creds.split(":"),
                            verbose=_args.verbose).downloadDoc()
    
    _ext = os.path.basename(_exemplarDoc.getDoc()['paths'][0]).split(".")[-1]
    _exemplarFilename = "/tmp/exemplar.%s" % (_ext)
    _exemplarDoc.downloadWebImage(_exemplarFilename)
            
    _cnt = 0
    _tenth = 1
    _stats = {
        'total_processed': 0,
        'skipped_already_tagged': 0,
        'skipped_no_person': 0,
        'faces_found': 0
    }

    for _id in _allIds:
        if _id != _args.exemplarId:
            _stats['total_processed'] += 1
            _imageDoc = ImageDoc(_args.db, _id,
                                 _args.creds.split(":"),
                                 verbose=_args.verbose).downloadDoc()
            _ext = os.path.basename(_imageDoc.getDoc()['paths'][0]).split(".")[-1]
            #print("DEBUG(%s:%s): tagset: %s" % (__name__, nowstr(), _tagset))

            # Check if tag already exists if tag parameter is provided
            _skip_aws = False
            _skip_reason = None
            if 'tags' in _imageDoc.getDoc():
                for tag in _imageDoc.getDoc()['tags']:
                    if tag.get('Name') == _args.tag:
                        _skip_aws = True
                        _skip_reason = "already tagged as '%s'" % _args.tag
                        _stats['skipped_already_tagged'] += 1
                        break

            # Skip if no "person" tag exists
            if not _skip_aws and 'tags' in _imageDoc.getDoc():
                _has_person = False
                for tag in _imageDoc.getDoc()['tags']:
                    if tag.get('Name', '').lower() == 'person':
                        _has_person = True
                        break
                if not _has_person:
                    _skip_aws = True
                    _skip_reason = "no 'person' tag found"
                    _stats['skipped_no_person'] += 1

            if _skip_aws and _args.verbose:
                print("DEBUG(%s:%s): skipping AWS call for %s - %s" %
                      (__name__, nowstr(), _id, _skip_reason))

            if not _skip_aws:
                _filename = "/tmp/candidate%d.%s" % (_cnt, _ext)
                if _args.verbose:
                    print("DEBUG(%s:%s): downloading webimage to: %s" %
                          (__name__, nowstr(), _filename))
                _imageDoc.downloadWebImage(_filename)
                _similarity = compare_faces(_exemplarFilename, _filename)
                os.remove(_filename)
                if _similarity != None:
                    _stats['faces_found'] += 1
                    print("INFO(%s:%s): found %s in %s (score: %3.1f%%)" %
                          (__name__, nowstr(), _args.tag, _id, float(_similarity)))
            
            # report progress
            if (_cnt <= _tenth*len(_allIds)/10.0) and (_cnt+1 > _tenth*len(_allIds)/10.0):
                print("INFO(%s:%s): %d%% complete..." % (__name__, nowstr(), _tenth*10))
                _tenth += 1
                
            _cnt += 1

    # Print statistics at the end
    print("")
    print("STATS(%s:%s): Processing Results" % (__name__, nowstr()))
    print("STATS(%s:%s): ------------------" % (__name__, nowstr()))
    print("STATS(%s:%s): Total images processed: %d" % (__name__, nowstr(), _stats['total_processed']))
    print("STATS(%s:%s): Skipped (already tagged): %d" % (__name__, nowstr(), _stats['skipped_already_tagged']))
    print("STATS(%s:%s): Skipped (no person tag): %d" % (__name__, nowstr(), _stats['skipped_no_person']))
    print("STATS(%s:%s): Total AWS calls made: %d" % (__name__, nowstr(), 
          _stats['total_processed'] - _stats['skipped_already_tagged'] - _stats['skipped_no_person']))
    print("STATS(%s:%s): Faces found: %d" % (__name__, nowstr(), _stats['faces_found']))
    print("")
