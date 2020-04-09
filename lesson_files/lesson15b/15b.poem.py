body = []
while (True):
    S = input()
    if (S=="###"): break
    body.extend(S.split())
for i in range(0, len(body)): body[i] = body[i].lower()
opt = 0
tmp = 0
for word in body: opt = max(opt, body.count(word))
for word in body:
    if body.count(word)==opt:
        res = word
        tmp += 1

if tmp > opt: print("Error: not a unique max")
print(res)



                                    

    
