#!/usr/bin/python3

"""this program is being used instead of php's built-in mailer because
the latter is not sending the domain correctly for the sender"""

print('start', end=' ')

import email.message, email.mime.text, smtplib, sys

from email.utils import parseaddr, formataddr

from email.header import Header

from email.charset import Charset

lines = sys.stdin.readlines()

def mohel(s):
    if s[-1]=='\n': return s[:-1]
    return s

def format_address(name, email):
    if not name:
        return email
    name = Charset('iso-8859-1').header_encode(name)
#    name = str(Header(name, 'iso-8859-1'))
#    print(name)
    return formataddr((name, email))

mFrom = mohel(lines[0])
mTo = mohel(lines[1])
mSubject = mohel(lines[2])
mBody = ''.join(lines[3:])

sender_name, sender_addr = parseaddr(mFrom)
recipient_name, recipient_addr = parseaddr(mTo)

print('parsed', end=' ')

composed = email.mime.text.MIMEText(mBody.encode('UTF-8'), _charset='UTF-8')
composed['from'] = format_address(sender_name, sender_addr)
composed['to'] = format_address(recipient_name, recipient_addr)
composed['subject'] = Header(mSubject, 'UTF-8')

print('composed', end=' ')

srv = smtplib.SMTP('localhost')
srv.send_message(composed)
srv.quit()

print('sent', end=' ')
