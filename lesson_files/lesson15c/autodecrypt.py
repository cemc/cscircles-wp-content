def encrypt(text, shift):
    res = ""
    for ch in text:
        e = ord(ch)-ord('A')
        if (ch.isalpha()):
            e += shift
            e %= 26
        res += chr(ord('A')+e)
    return res
        
def goodness(S):
    res = 0
    for ch in S: res += (letterGoodness[ord(ch)-ord('A')] if (ch!=' ') else 0)
    return res

def decode(S):
    maxg = -1
    for i in range(26): maxg = max(maxg, goodness(encrypt(S, i)))
    for i in range(26):
        if maxg == goodness(encrypt(S, i)):
            print(encrypt(S, i))

decode(input())
