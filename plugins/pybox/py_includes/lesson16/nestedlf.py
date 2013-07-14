def nestedListContains(NL, target):
    if NL==target:
        return True
    if isinstance(NL, int):
        return False
    for i in range(0, len(NL)):
        if nestedListContains(NL[i], target):
            return True
    return False
