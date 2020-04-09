import re
o2 = ''.join(filter(lambda x: x.isalpha(), Output))
e2 = ''.join(filter(lambda x: x.isalpha(), Expected))
linediff = Output.count("\n")-Expected.count("\n")

if (Output == ""):
    print("N"+_("Your program produced no output. Please try again."))
elif (linediff > 0):
    print("N"+_("Your program produced too many lines of output. Please try again."))
elif (linediff < 0):
    print("N"+_("Your program produced too few lines of output. Please try again."))
elif (Output == Expected):
    print("Y"+_("Exactly correct, good job!"))
elif (Output.upper() == Expected.upper()):
    print("N"+_("Almost correct but ensure you correctly use upper and lower case."))
elif (o2 == e2):
    print("N"+_("Almost correct but ensure you correctly use punctuation and spacing."))
elif (o2.upper() == e2.upper()):
    print("N"+_("Partially correct; ensure upper/lower case, punctuation, and spacing are correct."))
else:
    print("N"+_("Incorrect answer, please try again."))

