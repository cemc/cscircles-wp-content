def nestedListSum(NL):
    if isinstance(NL, int):     # case (a): NL is an integer
        return NL               # base case

    sum = 0                     # case (b): NL is a list of nested lists
    for i in range(0, len(NL)): # add subsums from each part of the main list
        sum = sum + nestedListSum(NL[i])
    return sum                  # all done
