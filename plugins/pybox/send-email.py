#!/usr/bin/python3

"""this program is being used instead of php's built-in mailer because
the latter is not sending the domain correctly for the sender"""

import email.message, email.mime.text, smtplib, sys

lines = sys.stdin.readlines()

def mohel(s):
    if s[-1]=='\n': return s[:-1]
    return s

mFrom = mohel(lines[0])
mTo = mohel(lines[1])
mSubject = mohel(lines[2])
mBody = ''.join(lines[3:])

composed = email.mime.text.MIMEText(mBody)

composed['from'] = mFrom
composed['to'] = mTo
composed['subject'] = mSubject

srv = smtplib.SMTP('localhost')
srv.send_message(composed)
srv.quit()
