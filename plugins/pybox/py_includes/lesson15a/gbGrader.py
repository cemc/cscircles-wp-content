def gradeHelper(expected):
    result= (
        getBASIC()
    )
    if (type(result) != type([])):
        return (False, "getBASIC() should return a list, but returned a " + str(type(result)))
    if (len(result) != len(expected)):
        return (False, "getBASIC() should return a list of length " + str(len(expected)) + " but returned " + str(len(result)))
    for i in range(0, len(result)):
        if (type(result[i]) != type("")):
            return (False, "getBASIC() should return a list of strings, but index " + str(i) + " had type " + str(type(result[i])))
        if (result[i] != expected[i]):
            return (False, "getBASIC() returned '" + result[i] + "' in index " + str(i) + ", but should have had '" + expected[i] + "'")
    return (True, "Correct!")

def grade(expected):
    ok, msg = gradeHelper(expected)
    print(chr(0), end="")
    if (ok): print("Y", end="")
    else: print("N", end="")
    print(msg)

