def gradeHelper(expected):
    result= (
        getBASIC()
    )
    if (type(result) != type([])):
        return (False, "getBASIC() 应该返还一个列表, 但却返还了一个 " + str(type(result)))
    if (len(result) != len(expected)):
        return (False, "getBASIC() 应该返还一个长度为 " + str(len(expected)) + " 的列表，但却返还了 " + str(len(result)))
    for i in range(0, len(result)):
        if (type(result[i]) != type("")):
            return (False, "getBASIC() 应该返还一个字符串列表, 但索引 " + str(i) + " 是类型 " + str(type(result[i])))
        if (result[i] != expected[i]):
            return (False, "getBASIC() 返还了 '" + result[i] + "' 在索引 " + str(i) + "中, 但它应该是 '" + expected[i] + "'")
    return (True, "正确!")

def grade(expected):
    ok, msg = gradeHelper(expected)
    print(chr(0), end="")
    if (ok): print("Y", end="")
    else: print("N", end="")
    print(msg)

