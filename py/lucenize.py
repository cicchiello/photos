#!/usr/bin/python

import re

class Lucenize():

    def __init__(self):
        pass

    def _synonyms(self, words):
        Synonyms = {'dad': 'frank', 'mom': 'rosemary'}
        return [Synonyms[word] if word in Synonyms else word for word in words]

    def _lower(self, words):
        return [word.lower() for word in words]

    def _xspurious(self, words):
        Spurious = ["png", "jpg", "and", "zzz", "zz", "zc", "zb", "za"] + \
            [c for c in "abcdefghijklmnopqrstuvwxyz"] + \
            [c for c in "ABCDEFGHIJKLMNOPQRSTUVWXYZ"] + \
            [c for c in '1234567890']

        #print("words: %s" % words)
        _x = list(set([word for word in words if not word in Spurious]))
        return list(set([word for word in _x if word[0] != "_"]))

    def _tokenize(self, msg):
        #print("msg: %s" % msg)
        #return re.findall("[A-Z0-9]{2,}(?![a-z0-9])|[A-Z0-9][a-z0-9]+(?=[A-Z0-9])|[\w]+",msg)
        return re.findall("[A-Z0-9]{2,}(?![a-z0-9])|[A-Z0-9][a-z0-9]+(?=[A-Z0-9])|[a-zA-Z0-9]+",msg)
    
    def keywords(self, msg):
        return self._synonyms(self._lower(self._xspurious(self._tokenize(msg))))
    

def test():
    _msg = "./Dad's\ Memorial/Dad-obit-head.png"
    print("INFO(%s): input: %s" % (__name__, _msg))
    print("INFO(%s): lucenize(input): %s" % (__name__, Lucenize().keywords(_msg)))

    _l = Lucenize()
    print("DEBUG(%s): tokenize(%s): %s" % (__name__, _msg, _l._tokenize(_msg)))
    print("DEBUG(%s): xspurious(%s): %s" % (__name__, _l._tokenize(_msg), _l._xspurious(_l._tokenize(_msg))))
    print("DEBUG(%s): lower(%s): %s" % (__name__, _l._xspurious(_l._tokenize(_msg)), _l._lower(_l._xspurious(_l._tokenize(_msg)))))
    print("DEBUG(%s): synonyms(%s): %s" % (__name__, _l._lower(_l._xspurious(_l._tokenize(_msg))), _l._synonyms(_l._lower(_l._xspurious(_l._tokenize(_msg))))))


    
if __name__ == "__main__":
    import sys
    import datetime
    import argparse
    from argparse import RawTextHelpFormatter
    
    def nowstr():
        return datetime.datetime.today().strftime('%Y-%b-%d %H:%M:%S')

    _description = 'lucenize given path'
    _epilog = '\n\nThis normalizes the metadata from the path\n\n'
    _parser = argparse.ArgumentParser(prog=sys.argv[0], description=_description, \
                                      epilog=_epilog, formatter_class=RawTextHelpFormatter)

    _parser.add_argument('-pic', nargs='?', required=True, help='path to image file')
    
    _args = _parser.parse_args(args=sys.argv[1:])

    print("ECHO(%s:%s): pic: %s" % (__name__, nowstr(), _args.pic))
    print("INFO(%s): lucenize(%s): %s" % (__name__, _args.pic, Lucenize().keywords(_args.pic)))

    
