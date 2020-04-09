#!/bin/sh
# converts all .pdfs to .pngs
# needs ImageMagick to be installed
mogrify -density 200 -trim -format png *.pdf