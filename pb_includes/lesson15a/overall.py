def getBASIC():
   prog = []
   while True:
       S = input()
       prog.append(S)
       if S.split()[-1]=='END':
           return prog
def findLine(prog, target):
    for i in range(0, len(prog)):
        w = prog[i].split()
        if w[0]==target:
            return i
def execute(prog):
    location = 0
    visited = [False] * len(prog)
    while True:
        if location == len(prog)-1:
            return "success"
        if (visited[location]):
            return "infinite loop"
        visited[location] = True
        target = prog[location].split()[2]
        location = findLine(prog, target)
print(execute(getBASIC()))
