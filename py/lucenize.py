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
        Spurious = ["png", "jpg"] + \
            [c for c in "abcdefghijklmnopqrstuvwxyz"] + \
            [c for c in "ABCDEFGHIJKLMNOPQRSTUVWXYZ"] + \
            [c for c in '1234567890']

        return list(set([word for word in words if not word in Spurious]))

    def _tokenize(self, msg):
        return re.findall("[A-Z]{2,}(?![a-z])|[A-Z][a-z]+(?=[A-Z])|[\w]+",msg)
    
    def keywords(self, msg):
        return self._synonyms(self._lower(self._xspurious(self._tokenize(msg))))
    

if __name__ == "__main__":
    _msg = "./Dad's\ Memorial/Dad-obit-head.png"
    print("INFO(%s): input: %s" % (__name__, _msg))
    print("INFO(%s): lucenize(input): %s" % (__name__, Lucenize().keywords(_msg)))

    _l = Lucenize()
    print("DEBUG(%s): tokenize(%s): %s" % (__name__, _msg, _l._tokenize(_msg)))
    print("DEBUG(%s): xspurious(%s): %s" % (__name__, _l._tokenize(_msg), _l._xspurious(_l._tokenize(_msg))))
    print("DEBUG(%s): lower(%s): %s" % (__name__, _l._xspurious(_l._tokenize(_msg)), _l._lower(_l._xspurious(_l._tokenize(_msg)))))
    print("DEBUG(%s): synonyms(%s): %s" % (__name__, _l._lower(_l._xspurious(_l._tokenize(_msg))), _l._synonyms(_l._lower(_l._xspurious(_l._tokenize(_msg))))))
