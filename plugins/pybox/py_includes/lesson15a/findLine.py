def findLine(prog, target):
    for i in range(0, len(prog)):
        w = prog[i].split()
        if w[0]==target:
            return i
