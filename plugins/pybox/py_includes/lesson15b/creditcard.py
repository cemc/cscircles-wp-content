def check(S):
    if (len(S) != 19): return False
    sp = [4, 9, 14]
    sum = 0
    for i in range(19):
        if i in sp:
            if S[i] != ' ': return False
        else:
            if (S[i].isdigit()): 
                sum += int(S[i])
            else: return False
    return (sum%10 == 0)
