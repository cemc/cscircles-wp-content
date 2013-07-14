# based on real-per-line.py
from _UTILITIES import _code
def f(): 
 for i in range(0, len(ExpectedLines)):
    if len(OutputLines)<=i:
        return "N"+_("Not enough lines of output: {0} expected, {1} found").format(str(len(ExpectedLines)),
                                                                                   str(len(OutputLines)))
    o = sfloat(OutputLines[i])
    if (o==None): return "N"+_("Error: Output line {0} '{1}' was not a number").format(str(i+1), _code(OutputLines[i]))
    if not realClose(float(ExpectedLines[i]), o):
        return "N"+_("Output line {0}, value {1}, did not match expected value {2}").format(
            str(i+1), OutputLines[i], ExpectedLines[i])
 if len(OutputLines)>len(ExpectedLines):
     return "N"+_("Too many lines of output: {0} expected, {1} found").format(
         str(len(ExpectedLines)), str(len(OutputLines)))
 res = "Y"+_("Correct!")+" "+_("Here is the plot of the output:")+"<pre>"
 for i in range(0, len(ExpectedLines)):
     res += "*"*int(0.5+sfloat(OutputLines[i]))+"\n"
 return res+"</pre>"
print(f())
