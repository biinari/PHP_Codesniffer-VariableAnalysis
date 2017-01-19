#!/bin/bash
cd vendor/
ln -sf ../../../ksjogo/ ksjogo
cd ..
vendor/bin/phpunit
