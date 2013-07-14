for i in range(2, 8):
    o1 = "chr:  "
    o2 = "asc: "
    for j in range(16*i, 16*i+16):
        o1 = o1 + chr(j) + "   "
        o2 = o2 + str(j) + " "*(4-len(str(j)))
    print(o1)
    print(o2)
