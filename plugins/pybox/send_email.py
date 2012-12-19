#!/usr/bin/python3

"""Usage:
- first line is sender in the format: full name <address@doma.in>
- second line is the recipient in the same format
- third line is the subject
- fourth+ lines are the body

NB: this program is being used instead of php's built-in mailer because
the latter is not sending the domain correctly for the sender"""

import email.message, email.mime.text, smtplib, sys
from email.utils import parseaddr, formataddr
from email.header import Header
from email.charset import Charset

def mohel(s):
    if s[-1]=='\n': return s[:-1]
    return s

def format_address(name, email):
    if not name:
        return email
    name = Charset('iso-8859-1').header_encode(name)
    return formataddr((name, email))

def send_unicode_email(mFrom, mTo, mSubject, mBody):
    sender_name, sender_addr = parseaddr(mFrom)
    recipient_name, recipient_addr = parseaddr(mTo)

    composed = email.mime.text.MIMEText(mBody.encode('UTF-8'),
                                        _charset='UTF-8')
    composed['Reply-To'] = format_address(sender_name, sender_addr)
    composed['To'] = format_address(recipient_name, recipient_addr)
    composed['Subject'] = Header(mSubject, 'UTF-8')
    composed['From'] = format_address(sender_name,
                                      'bounces@cscircles.cemc.uwaterloo.ca')

    srv = smtplib.SMTP('localhost')
    srv.send_message(composed)
    srv.quit()


def main():
    lines = sys.stdin.readlines()
    mFrom = mohel(lines[0])
    mTo = mohel(lines[1])
    mSubject = mohel(lines[2])
    mBody = ''.join(lines[3:])
    send_unicode_email(mFrom, mTo, mSubject, mBody)

if __name__ == '__main__':
    main()
