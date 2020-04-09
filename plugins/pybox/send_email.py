#!/usr/bin/python3

BOUNCE_ADDRESS = 'bounces@cscircles.cemc.uwaterloo.ca'
NOREPLY_ADDRESS = 'noreply@cscircles.cemc.uwaterloo.ca'
WEBSITE_URL = 'https://cscircles.cemc.uwaterloo.ca/' # with trailing slash

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
    name = str(Header(name, 'utf-8'))
    return formataddr((name, email))

def send_unicode_email(mFrom, mTo, mSubject, mBody, noreply = False):
    sender_name, sender_addr = parseaddr(mFrom)
    recipient_name, recipient_addr = parseaddr(mTo)

    composed = email.mime.text.MIMEText(mBody.encode('UTF-8'),
                                        _charset='UTF-8')
    composed['Reply-To'] = format_address(sender_name, sender_addr)
    composed['To'] = format_address(recipient_name, recipient_addr)
    composed['Subject'] = Header(mSubject, 'UTF-8')
    addr = BOUNCE_ADDRESS if (not noreply) else NOREPLY_ADDRESS
    composed['From'] = format_address(sender_name, addr)

    try:
        srv = smtplib.SMTP('localhost')
        srv.send_message(composed)
        srv.quit()
        return 0
    except:
        report_error([mFrom, mTo, mSubject, mBody])
        return -1

def report_error(msgArray):
    import os, json, traceback
    from time import localtime, strftime
    log_filename = "send_email_errors_log.txt"
    logged = False
    errmsg = 'Failed at '+strftime("%a, %d %b %Y %H:%M:%S", localtime())+': '+json.dumps(msgArray)
    try:
        if os.path.isfile(log_filename):
            with open(log_filename, 'a', encoding='utf-8') as f:
                print(errmsg, file=f)
                traceback.print_exc(file=f)
                logged = True
        else:
            with open(log_filename, 'w', encoding='utf-8') as f:
                print(errmsg, file=f)
                traceback.print_exc(file=f)
                logged = True
    except:
        pass
    if (not logged):
        print('Could not write to ' + log_filename)
        print(errmsg)
        traceback.print_exc()
                
def main():
    lines = sys.stdin.readlines()
    mFrom = mohel(lines[0])
    mTo = mohel(lines[1])
    mSubject = mohel(lines[2])
    mBody = ''.join(lines[3:])
    return send_unicode_email(mFrom, mTo, mSubject, mBody)

if __name__ == '__main__':
    retcode = main()
    sys.exit(retcode)
