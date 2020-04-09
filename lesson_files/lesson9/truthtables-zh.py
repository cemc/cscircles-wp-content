print("  A      B       not A  not B  A and B  A or B")
print("----------------------------------------------")
for A in [False, True]:               # 对序列使用"for"循环	
    for B in [False, True]:           # 会在lesson 13讲到
        print(A, " ", B, "   ", not A, " ", not B, " ", A and B, " ", A or B)	
