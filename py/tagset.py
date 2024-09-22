#!/usr/bin/python3

# The following is a derivative of code found at: https://docs.aws.amazon.com/rekognition/latest/dg/images-bytes.html
#   Copyright 2018 Amazon.com, Inc. or its affiliates. All Rights Reserved.
#   PDX-License-Identifier: MIT-0 (For details, see https://github.com/awsdocs/amazon-rekognition-developer-guide/blob/master/LICENSE-SAMPLECODE.)

import boto3
import datetime
import calendar
import time

from lucenize import Lucenize


def nowstr():
    return datetime.datetime.today().strftime('%Y-%b-%d %H:%M:%S')



class Tagset():

    def __init__(self, tag_arr=[], verbose=False):
        self._verbose = verbose
        self._veryVerbose = False
        self._tags = tag_arr
        self._names = set([_t['Name'] for _t in tag_arr])

    @staticmethod
    def create_from_json(jsonstr):
        _tagset = Tagset()
        for _tag in json.loads(jsonstr):
            _tagset.append_tag(_tag)
            
        return _tagset
        
    
    @staticmethod
    def merge_tag(tag1, tag2):
        # prefer 'user' over 'rekognition' over 'path-tokenize'
        if tag1['source'] == 'user':
            return tag1
        elif tag2['source'] == 'user':
            return tag2
        elif tag1['source'] == 'rekognition':
            return tag1
        elif tag2['source'] == 'rekognition':
            return tag2
        else:
            return tag1
    
    @staticmethod
    def merge(set1, set2):
        _merged = Tagset()
        for _tag in set1.get_tag_arr():
            if _tag['Name'] in set2.get_tagnames():
                _merged.append_tag(Tagset.merge_tag(_tag, set2.get_tag(_tag['Name'])))
            else:
                _merged.append_tag(_tag)
        for _tag in set2.get_tag_arr():
            if not _tag['Name'] in _merged.get_tagnames():
                _merged.append_tag(_tag)

        return _merged

    def get_tag(self, name):
        for _tag in self._tags:
            if _tag['Name'] == name:
                return _tag
            
        return None
    
    def get_tag_arr(self):
        return self._tags

    def get_tagnames(self):
        return self._names

    def append_tag(self, tag):
        if tag['Name'] in self._names:
            return

        self._names.add(tag['Name'])
        self._tags.append(tag)

        
    def append_metadata_tags(self, str):
        for path_keyword in Lucenize().keywords(str):
            if not path_keyword in self._names:
                tag = {}
                tag['Name'] = path_keyword
                tag['Confidence'] = 99.9
                tag['Instances'] = []
                tag['Parents'] = []
                tag['Aliases'] = []
                tag['Categories'] = []
                tag['timestamp'] = calendar.timegm(time.gmtime())
                tag['source'] = "path-tokenize"
                self._tags.append(tag)
                self._names.add(path_keyword)
    
    def recognize(self, photo):
        _client=boto3.client('rekognition')
   
        with open(photo, 'rb') as image:
            _r = _client.detect_labels(Image={'Bytes': image.read()})
        
        if self._veryVerbose:
            print('DEBUG(%s:%s): full response: %s' % (__name__, nowstr(), str(_r)))
            
        if self._verbose:
            print('INFO(%s:%s): Detected labels in %s' % (__name__, nowstr(), photo))

        if self._verbose:
            for _label in _r['Labels']:
                print("INFO(%s:%s): %s : %s" % (__name__, nowstr(), _label['Name'], str(_label['Confidence'])))
                
        for _label in _r['Labels']:
            _label['Name'] = _label['Name'].lower()
            if not _label['Name'] in self._names:
                _label['timestamp'] = calendar.timegm(time.gmtime())
                _label['source'] = "rekognition"
                self._names.add(_label['Name'])
                self._tags.append(_label)

        
    
if __name__ == "__main__":
    import sys
    import json
    
    import argparse
    from argparse import RawTextHelpFormatter
    
    from os.path import exists

    _description = 'Use AWS Rekognition to produce a list of tags for a given photo'
    _epilog = '\n\nexample: %s -pic ./my_photo.jpg\n\n' % sys.argv[0]
    _parser = argparse.ArgumentParser(prog=sys.argv[0], description=_description, \
                                      epilog=_epilog, formatter_class=RawTextHelpFormatter)

    _parser.add_argument('-pic', nargs='?', required=True, help='path to image file')
    _parser.add_argument('-meta', nargs='?', required=False, help='arbitrary string to tokenize and add')
    _parser.add_argument('-verbose', default=False, action='store_true', help='provide extra debug output')

    _args = _parser.parse_args(args=sys.argv[1:])

    if not exists(_args.pic):
        print("\nERROR(%s:%s): %s not found\n" % (__name__, nowstr(), _args.pic))
        _parser.print_help()
        exit()
    
    print("ECHO(%s:%s): pic: %s" % (__name__, nowstr(), _args.pic))
    print("ECHO(%s:%s): verbose: %s" % (__name__, nowstr(), _args.verbose))
    print("ECHO(%s:%s): meta: %s" % (__name__, nowstr(), _args.meta))

    _t = Tagset(verbose=_args.verbose)
    _t.recognize(_args.pic)

    if not _args.meta is None:
        _t.append_metadata_tags(_args.meta)
        
    _r = _t.get_tag_arr()
    print("INFO(%s:%s): tags: %s" % (__name__, nowstr(), json.dumps(_r)))
    
