def f():
    if len(OutputLines)!=1:
        return "N"+_("Error: Your output should consist of exactly 1 line.")
    S = OutputLines[0]
    if S.count(" ")>0:
        return "N"+_("Error: Your output should not contain any spaces.")
    if S[-1]!=ExpectedLines[0][-1]:
        return "N"+_("Error: The last character of your output is wrong.")
    if sfloat(S[0:-1])==None:
        return "N"+_("Error: Could not interpret {} as a float.").format("'"+S[0:-1]+"'")
    if realClose(sfloat(ExpectedLines[0][0:-1]), sfloat(S[0:-1])):
        return "Y"
    return "N"+_("Error: Numbers are different.")
print(f())

