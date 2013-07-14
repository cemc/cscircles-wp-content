S = input()
D = float(S[0:-1])
if S[-1]=='F':
    print((D-32)/1.8, 'C', sep='')
else:
    print(D*1.8+32, 'F', sep='')
            
