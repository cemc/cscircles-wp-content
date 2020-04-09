#!/bin/sh
# converts all .pdfs to .pngs
# needs ImageMagick to be installed
mogrify -density 110 -trim -format png hypotenuse*.pdf
mogrify -density 150 -trim -format png distance*.pdf