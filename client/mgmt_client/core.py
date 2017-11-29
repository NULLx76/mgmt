#!/usr/bin/env python
#coding=utf-8

import apt

cache=apt.Cache()
cache.update()
cache.open(None)
cache.upgrade()



