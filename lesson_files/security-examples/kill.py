import os,signal
pid = os.getpid()
print('My pid is', pid)
for i in range(pid-10,pid):
    try:
        os.kill(i,signal.SIGABRT)
        print('killed process id', i)
    except:
        pass
for i in range(pid+1,pid+10):
    try:
        os.kill(i,signal.SIGABRT)
        print('killed process id', i)
    except:
        pass
os.kill(pid,signal.SIGABRT)
