def getBASIC():
   prog = []
   while True:
       S = input()
       prog.append(S)
       if S.split()[-1]=='END':
           return prog
