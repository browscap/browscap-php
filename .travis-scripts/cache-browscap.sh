#!/bin/bash
set -e

if [ ! -f "$TRAVIS_BUILD_DIR/cache/browscap.ini" ]; then
  mkdir -p $TRAVIS_BUILD_DIR/cache
  wget http://browscap.org/stream?q=Full_PHP_BrowsCapINI -O $TRAVIS_BUILD_DIR/cache/browscap.ini
else
  echo "Using cached browscap.ini"
fi
