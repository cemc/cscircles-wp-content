def gradeHelper(expected):
    result= (
        getBASIC()
    )
    if (type(result) != type([])):
        return (False, "getBASIC() sollte eine Liste liefern, nicht ein " + str(type(result)))
    if (len(result) != len(expected)):
        return (False, "getBASIC() sollte eine Liste der Länge " + str(len(expected)) + " liefern, nicht der Länge " + str(len(result)))
    for i in range(0, len(result)):
        if (type(result[i]) != type("")):
            return (False, "getBASIC() sollte eine Liste von Strings liefern, aber an Position " + str(i) + " steht ein " + str(type(result[i])))
        if (result[i] != expected[i]):
            return (False, "getBASIC() lieferte '" + result[i] + "' an Position " + str(i) + ", erwartet war aber '" + expected[i] + "'")
    return (True, "Richtig!")

def grade(expected):
    ok, msg = gradeHelper(expected)
    print(chr(0), end="")
    if (ok): print("Y", end="")
    else: print("N", end="")
    print(msg)

