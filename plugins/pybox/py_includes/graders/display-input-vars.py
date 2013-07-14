# display input in the format
# "In this case we set a=<line1>, b=<line2>, ..."
#
# grade one-line answer by comparing it to the expected output

def f():
 firstVar = 'a'

 if len(OutputLines) == 0:
  print("N"+_("Your program produced no output."))
  return

 if len(OutputLines) > 1:
  print("N"+_("Your program produced more than one line of output.\nIt should print exactly one line."))
  return

 if ExpectedLines[0].strip() == OutputLines[0].strip():
  print("Y", end="")
 else:
  print("N", end="")

 print(_("In this test case we set"))
 numLines = len(InputLines)
 for i in range(len(InputLines)):
  print(chr(ord(firstVar)+i), " = ", InputLines[i], end="")
  if i < numLines - 2: print(", ", end="")
  elif i == numLines - 2: print(_(" and "), end="")
  elif i == numLines - 1: print(".") 

 print(_("Your program printed "), OutputLines[0].strip(), ".", sep="")
 if ExpectedLines[0].strip() == OutputLines[0].strip():
  print(_("Your answer is correct!"))
 else:
  print(_("The correct answer is "), ExpectedLines[0].strip(), ".", sep="") 

f()
