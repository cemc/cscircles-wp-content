#!/bin/bash
rm -rf *.{mo,po}
rm -rf */*.{mo,po}
wget -A mo,po -r -l 2 -nH --cut-dirs=5 http://svn.automattic.com/wordpress-i18n/fr_FR/branches/3.7/messages/
wget -A mo,po -r -l 2 -nH --cut-dirs=4 http://svn.automattic.com/wordpress-i18n/de_DE/trunk/messages/
cd twentyeleven
wget -A mo,po -r -l 2 -nH --cut-dirs=5 http://svn.automattic.com/wordpress-i18n/fr_FR/branches/3.6/messages/twentyeleven
rm robots.txt
cd ..
rm robots.txt