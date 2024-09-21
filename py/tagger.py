#!/usr/bin/python3

# The following is a derivative of code found at: https://docs.aws.amazon.com/rekognition/latest/dg/images-bytes.html
#   Copyright 2018 Amazon.com, Inc. or its affiliates. All Rights Reserved.
#   PDX-License-Identifier: MIT-0 (For details, see https://github.com/awsdocs/amazon-rekognition-developer-guide/blob/master/LICENSE-SAMPLECODE.)

import boto3
import datetime


def nowstr():
    return datetime.datetime.today().strftime('%Y-%b-%d %H:%M:%S')



class Tagger():

    DEFAULT_CONFIDENCE = 72.0
    
    def __init__(self, threshold=None, verbose=False):
        self._threshold = threshold if not threshold is None else Tagger.DEFAULT_CONFIDENCE
        self._verbose = verbose

    def tag(self, photo):
        client=boto3.client('rekognition')
   
        with open(photo, 'rb') as image:
            response = client.detect_labels(Image={'Bytes': image.read()})
        
        if self._verbose:
            print('Detected labels in ' + photo)
            
        _r = []
        for label in response['Labels']:
            if label['Confidence'] >= self._threshold:
                if self._verbose:
                    print (label['Name'] + ' : ' + str(label['Confidence']))
                _r.append([label['Name'], label['Confidence']])

        return _r

    
if __name__ == "__main__":
    import sys
    
    import argparse
    from argparse import RawTextHelpFormatter
    
    from os.path import exists

    _defthresh = Tagger.DEFAULT_CONFIDENCE
    
    _description = 'Use AWS Rekognition to produce a list of tags for a given photo'
    _epilog = '\n\nexample: %s -pic ./my_photo.jpg -thresh 80\n\n' % sys.argv[0]
    _parser = argparse.ArgumentParser(prog=sys.argv[0], description=_description, \
                                      epilog=_epilog, formatter_class=RawTextHelpFormatter)

    _parser.add_argument('-pic', nargs='?', required=True, help='path to image file')
    _parser.add_argument('-thresh', nargs='?', required=False, help='confidence threshold percent (default=%s)' % str(_defthresh))
    _parser.add_argument('-verbose', default=False, action='store_true', help='provide extra debug output')

    _args = _parser.parse_args(args=sys.argv[1:])

    if not exists(_args.pic):
        print("\nERROR(%s:%s): %s not found\n" % (__name__, nowstr(), _args.pic))
        _parser.print_help()
        exit()
    
    _threshold = float(_args.thresh) if not _args.thresh is None else _defthresh
    
    print("ECHO(%s:%s): pic: %s" % (__name__, nowstr(), _args.pic))
    print("ECHO(%s:%s): threshold: %s%%" % (__name__, nowstr(), _threshold))

    _r = Tagger(threshold=_threshold,verbose=_args.verbose).tag(_args.pic)
    print("INFO(%s:%s): tags: %s" % (__name__, nowstr(), str(_r)))
    
