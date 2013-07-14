def f():
 for i in range(0, len(ExpectedLines)):
    if len(OutputLines)<=i:
        return "N"+_("Not enough lines of output: {0} expected, {1} found").format(
            str(len(ExpectedLines)), str(len(OutputLines)))
    if not OutputLines[i].strip()==ExpectedLines[i].strip():
        return "N"+_("Error: output line {0} {1} did not match expected line {2}").format(
            str(i),
            "<pre>" + OutputLines[i] + "</pre>",
            "<pre>" + ExpectedLines[i] + '</pre>')
 if len(OutputLines)>len(ExpectedLines):
     return "N"+_("Too many lines of output: {0} expected, {1} found").format(
         str(len(ExpectedLines)), str(len(OutputLines)))
 return "Y"+_("Correct!")
print(f())
