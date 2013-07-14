T = input()
D = int(input())
H = int(T[:2])
M = int(T[3:])
M2 = (M+D)%60
H2 = (H+(M+D)//60)%24
S = ""
if H2<10:
     S = S+"0"
S = S+str(H2)+":"
if M2<10:
    S = S+"0"
print(S+str(M2))
