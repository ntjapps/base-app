#!/bin/bash
chown nobody:nogroup -R .
cd storage
umask 0000
chmod 777 -R .
cd ..
