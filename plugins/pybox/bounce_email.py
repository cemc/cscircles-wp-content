#!/usr/bin/python3

# NB: for this to be effective you need to 
# 1. create a symlink /etc/smrsh/cscircles-bounce-email --> this file, and
# 2. create a line "bounces:  |cscircles-bounce-email" in /etc/aliases
# 3. ensure postfix is configured, running, not blocked by iptables, works with smrsh

import email.parser, email.message, email.mime.text, smtplib, sys, re
import send_email

received = email.parser.Parser().parse(sys.stdin)

textbody = ''
htmlbody = ''
for part in received.walk():
   if part.get_content_type() == 'text/plain':
      textbody += part.get_payload() + "\n"
   elif part.get_content_type() == 'text/html':
      htmlbody += part.get_payload() + "\n"
if textbody != "":
    origbody = textbody
else:
    origbody = htmlbody

link = re.search(send_email.WEBSITE_URL + '([^/]*)/([^#]*)#m', origbody)
if (link == None):
   linkhint = ' '
else:
   linkhint = '\n'+link.group(0)+'\n'

linkhint = linkhint.replace('=3D', '=')

# these two strings should remain SYNCED with bounce_email_strings.php.
# make any English change both here and there;
# then rebuild the .pot and .po files;
# after the .po is updated by a translator, update this file.

if (link != None and link.group(1) == 'poste'): # french
   bodytemplate = "Votre e-mail à {0} ne sera pas lu. Si vous avez répondu à pour l'aide sur un problème, vous devez utiliser le lien{1}dans le courriel précédent. \nUne copie de votre e-mail est copiée ci-dessous."
   bouncerName = 'Rebondeur Cercles informatiques'
elif (link != None and link.group(1) == 'post'): # german
   bodytemplate = "Deine Email an {0} wird nicht gelesen werden. Wenn du antwortest, um weitere Hilfsstellungen zu einem Problem zu erfragen, musst du den Link aus der vorherigen Email verwendenl \n{1}\nEs folgt eine Kopie deiner Email."
   bouncerName = 'EI:CSC (no-reply)'
else:
   bodytemplate = "Your e-mail to {0} will not be read. If you are replying to ask for follow-up help about a problem, you must use the link in the previous email{1} \nA copy of your e-mail follows."
   bouncerName = 'CS Circles Bouncer'

mFrom = '"' + bouncerName + '" <' + send_email.BOUNCE_ADDRESS + '>'
body = bodytemplate.format(send_email.BOUNCE_ADDRESS, linkhint)

body += "\n===\n" + origbody

subject = received.get("subject")
if subject[0:3].lower() != 're:':	
	subject = 'Re: ' + subject

mTo = received.get('from')

send_email.send_unicode_email(mFrom, mTo, subject, body)
   
