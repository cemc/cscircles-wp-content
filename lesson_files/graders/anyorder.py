def f():
    i = 0
    for ol in OutputLines:
        i += 1
        if (ExpectedLines.count(ol)==0):
            if (OutputLines[0:i].count(ol)>1):
                return "N"+_("Line {}: too many copies of '{}'").format(str(i), ol)
            return "N"+_("Line {}: not a correct output line: '{}'").format(str(i), ol)
        ExpectedLines.remove(ol)
    if len(ExpectedLines)>0:
        return "N"+_("Missing at least one line of output: '{}'").format(ExpectedLines[0])
    return "Y"+_("Correct!")
print(f())
