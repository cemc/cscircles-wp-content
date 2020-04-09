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
