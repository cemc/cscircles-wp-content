import os
from inspect import currentframe
print(os.getcwd()) #should work
print(os.getuid(), os.geteuid()) #should work
print(os.getgid(), os.getegid(), 1000, "should be same")
try:
    print("contents of /lib", os.listdir('/lib')) #should work
except OSError as E:
    print('ok')

print("currrent filename", currentframe().f_code.co_filename)
    
try:
    print('bad', os.listdir(os.getcwd())) #should work
except OSError as E:
    print("ok:", E)

try:
    print("bad:", os.listdir('/'))
except OSError as E:
    print("ok:", E)

try:
    print("contents of /scratch:", os.listdir('/scratch'))
except OSError as E:
    print("ok:", E)

try:
    print(len(open('safeexec.out').read()))
except IOError as E:
    print("ok:", E)

print(len(open(currentframe().f_code.co_filename).read()))
print(len(open('usercode').read()))
print(len(open('/static/_UTILITIES.py').read()))

try:
    print(open('/README').read())
except IOError as E:
    print('ok')

try:
    print(len(open('usercode').write('test')))
except IOError as E:
    print("ok:", E)

try:
    print(len(open('/static/_UTILITIES.py').write('test')))
except IOError as E:
    print("ok:", E)
    
