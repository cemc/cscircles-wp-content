def postalValidate(S):
     S = S.replace(' ', '')
     if len(S) != 6: return False
     for i in range(0, 6, 2):
         if not S[i].isalpha(): return False
     for i in range(1, 6, 2):
         if not S[i].isdigit(): return False
     return S.upper()
          
