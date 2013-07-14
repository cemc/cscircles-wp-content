def lowerChar(char):
    if char>='A' and char<='Z': return chr(ord(char)+32)
    else: return char

def lowerString(string):
    tmp=''
    for c in string: tmp=tmp+lowerChar(c)
    return tmp
