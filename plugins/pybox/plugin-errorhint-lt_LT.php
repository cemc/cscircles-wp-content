<?php
global $hintage;
$hintage = array(
                 // global means it was within a function body
                 "NameError: (global )?name '(.*)' is not defined" => '
Ši klaida reiškia, kad Python nepavyko rasti kintamojo ar funkcijos pavadinimu <code>$2</code>,
Gal sumaišėte rašybą - praleidot raidę ar parašėt mažąją vietoj didžiosios, ar pan.?
Pavyzdys:
<pre>
print(Max(3, 4)) # vietoj „Max“  turėtų būti „max“
</pre>
Gal kintamasis atsiras (jam bus priskirta reikšmė) tik vėliau? <br>O gal šioje programos srity (pvz., funkcijoje) negalioja kitur naudotas kintamasis?
<br/>
Veiksmų sekimas gali padėti suprasti, kurie kintamieji yra matomi klaidos momentu<br>
Ar gal tiesiog užmiršote tekstą parašyti kabutėse?
<pre>
print(Hello) # turėtų būti: print("Hello")
</pre>
',
                 "SyntaxError: invalid syntax" => '
Ši klaida reiškia, kad Python nesugebėjo suprasti užrašyto kodo,
nes jis neatitinka numatytų sintaksės taisyklių.
Dažnai tai gali nutikti dėl:
<ul>
 <li>užmirštos kabutės ar skliaustelio, </li>
 <li>ciklo, sąlygos ar funkcijos antraštės gale užmiršto dvitaškio,</li>
 <li>vietoj palyginimo veiksmo  <code>==</code>  parašius priskyrimą <code>=</code>,</li>
 <li>labai įvairių priežasčių, dažnai reikia patikrinti šalia esančias eilutes, kad rastum klaidą.</li>
</ul>
Pavyzdžiui:         
<pre>
if x > 0 # trūksta dvitaškio
   print(x, "yra teigiamas")
</pre>
',
"EOFError: EOF when reading a line" => "
Ši klaida reiškia, kad Python neberado įvedamų duomenų.
Arba buvo iškviesta <code>input()</code> dažniau negu reikia,
arba buvo pateikta per mažai įvesties. Pvz.,
<pre>
print(input()+input()) # bandys nuskaityti 2 eilutes
</pre>
Naudodami veiksmų sekimą, galite nustatyti, kiek kartų <code>input()</code> yra iškviečiama.
",

"TypeError: unsupported operand type\(s\) .*" =>
"
Python nemoka atlikti tokių veiksmų, kaip nurodyta, arba duomenų tipai netinkami.
Pvz.:
<pre>
print('5'+1) # teksto su skaičiumi sudėt nemoka :/
</pre>

Tokiais atvejais reikia atlikt 
<a href='http://cscircles.cemc.uwaterloo.ca/4-types/'>tipo pakeitimą</a>.

Naudodami veiksmų sekimą, galite nustatyti, kokio tipo duomenys yra prieš pat atliekant veiksmą.
",

"SyntaxError: unexpected EOF while parsing" =>
"
Ši klaida reiškia, kad Python bandant interpretuoti kodą, jis netikėtai baigėsi.
Patikrinkite, ar netrūksta uždarančių skliaustelių, pvz.:
<pre>
print(min(5,max(x,y)) # trūksta ')' gale
</pre>
Arba gal užmiršote veiksmų bloką po antraštės?
<pre>
if x > y: # trūksta veiksmų
</pre>
",

"IndentationError: unindent does not match any outer indentation level"
=>
"
Lygiavimo klaida: patraukimas atgal neatitinka jokio ankstesnio lygiavimo.<br> Pvz.:
<pre>
if x > y:
  print('ok')
 print('?') # reikia 0 arba 2 tarpų atitraukimo, bet ne 1
</pre>
Kai turite kelių lygmenų atitraukimą (pvz., ciklą cikle, ar sąlygą cikle), reik atidžiai lygiuoti ;).
",
"SyntaxError: EOL while scanning string literal"
=>
"
Nesusipratimas - teksto duomenys nesibaigė, tačiau baigėsi kodo eilutė.
Patikrinkite ar nepamiršote kabučių, pvz.:
<pre>
print('hello) # turėtų būti: print('hello')
</pre>
Jei tekste naudojate specialius simbolius (kabutes ar „<code>\</code>“),
neužmirškite prieš juos įterpti „<code>\</code>“.
<br>
PS.: Jei norite parašyti teksto duomenis per kelias eilutes, galite naudoti trigubas kabutes.
",
"IndentationError: unexpected indent"
=>
"
Lygiavimo klaida: nepagrįstas atitraukimas. <br><br>
Papildomi atitraukimai nuo krašo gali būti po ciklo/sąlygos/funkcijos antraščių (pasibaigiančių „<code>:</code>“).
Kažkur be reikalo pastūmėt dešinėn, pvz.:
<pre>
print('ok')
    print('Netinkami tarpai eilutės pradžioje')
</pre>",


"TabError: inconsistent use of tabs and spaces in indentation"
=>
"Tabuliacijos klaida: nenuoseklus tarpų ir tab'ų naudojimas eilučių lygiavimui. <br><br>
Nėra bendro susitarimo, kiek vienas tab'as atitinka tarpų, tačiau faile buvo naudojami ir tarpai, ir tab'ai.<br>
Python rekomenduojama naudoti tarpus, nes skirtinguose teksto redaktoriuose tab'ai gali būti vaizduojami skirtingo dydžio.
",

"IndentationError: expected an indented block"
=>
"Lygiavimo klaida: trūksta atitraukto bloko.<br><br>
This error indicates that Python expected indented code but not encounter it. Recall that after a <code>if</code>, <code>for</code>, <code>while</code> or <code>def</code> expression, an indentation is required for its enclosed statements.
<pre>
if name == 'Jim':
print('Hello') # should be indented
</pre>
",
"ValueError: invalid literal for (.*)\(\) with base .*"
=>
'Reikšmės klaida: netinkamas tekstas <code>$1</code> tipo  skaičiaus atpažinimui. <br><br>
Pvz.:
<pre>
y = int("5.4") # tiktų, jei būtų: float("5.4") or int("5") 
</pre>
Galite naudoti veiksmų sekimą, kad matytumėte, kokios buvo kintamųjų reikšmės įvykstant klaidai.',

"TypeError: '.*' object is not iterable"
=>
"Duomenų tipo klaida: objektas nėra tinkamas perrinkimui cikle. <br><br>
For cikle ar pan. bandomi naudoti perrinkimui netinkami duomenys,
pvz, vietoj sąrašo - paprastas skaičius.
Arba, pvz., funkcijoms <code>max()</code>/<code>min()</code>
reikia kelių reikšmių, kad būtų, ką palyginti:
<pre>
print(max(a)) # reiktų: print(max(a,b))
</pre>
",

"TypeError: '.*' object is not callable"
=>
"Duomenų tipo klaida: objekto negalima iškviesti kaip funkcijos.<br><br>
Ši klaida dažniausiai nutinka dėl neteisingo skliaustelių naudojimo - 
Python atrodo, kad kažkurt turi būti funkcija, nes po jos būna skliausteliai su duomenimis.
<pre>
5(4) # 5 nėra funkcija, gal turėjote omeny 5*4?
</pre>

Sudėtingose išraiškose  įsitikinkite, kad funkcijų argumentų skliausteliai teisingai sudėlioti, pvz.:
<pre>
print(min(1,2)(3,4)) # ką čia veikia (3,4)?
</pre>
",

"TypeError: can't multiply sequence by non-int of type '.*'"
=>
"Duomenų tipo klaida: seką (sąrašą/tekstą) galima padauginti tik iš sveiko skaičiaus.<br><br>

Python'e  <code>'hi'*2</code> bus <code>'hihi'</code>.
Bet negalima sąrašo ar teksto padauginti iš kito sąrašo/teksto ar nesveiko skaičiaus.
<pre>
[1, 2] * 1.5  # blogai 
(1, 2) * 1.5  # irgi blogai
[1, 2] * 3  # gerai - gausis [1, 2, 1, 2]
</pre>

O jei norite padauginti visas sąrašo elementų reikšmes iš skaičiaus, naudokite ciklą
(arba <a href='http://www.numpy.org/'>NumPy</a> galimybes).
",
                 "IndexError: (string|list) index out of range"
=>
'Numeracijos klaida: nurodytas per didelis sąrašo elemento (ar teksto simbolio) numeris.<br><br> 
Nurodytas per didelis  sąrašo (ar teksto eilutės) elemento numeris.
                Neužmirškite, kad Python\'e numeruojama nuo 0, o paskutinio elemento numeris yra (ilgis-1).

<pre>
c = pazymiai[i] # reik, kad būtų i >= 0, i < len(pazymiai)
</pre>
Galite naudot veiksmų sekimą, kad patikrintumėt, koks yra sąrašo/teksto ilgis ar indeksui naudojamo kintamojo reikšmė',

                 "SyntaxError: unexpected character after line continuation character"
=>
"Sintaksės klaida: netikėtas simbolis po eilutės perkėlimo simbolio.<br><br>
Norint perkelt nebaigtą python komandų sakinį į kitą eilutę, naudojamas simbolis <tt>\"\\\"</tt> (angl. „backslash“) - jį padėjus eilutėje nieko nebegalima rašyti.
".'
<pre>
x = 3 \\\\
+ 4        # x is 7
</pre>
'."
Šis simbolis taip pat naudojamas tekste užrašant specialius simbolius -- pvz,
<tt>\\t</tt> reiškia tabuliacijos ženklą, o <tt>\\n</tt> - \"Enter\" ženklą,<br>
o teksto viduje esanti kabutė (bet nereiškianti teksto pabaigos) - <tt>\\\"</tt>
o norint parašyti patį  \"\\\", jį reikia pakartoti 2 kartus -  \"\\\\\".
Gali būti, kad teksto eilutėje parašėte per daug ar per mažai simbolių <tt>\"\\\"</tt>, pvz.:
" . '
<pre>
print("a\\\\"\\\\") # a raidė, užkoduotas backslash\'as,
                    # teksto pabaiga (kabutė), netinkamas backslash\'as!
</pre>
',

"TypeError: Can't convert '.*' object to str implicitly" =>
"Duomenų tipo klaida: Negaliu tiesiogiai paversti objekto į tekstą. <br><br>
Python bandė sudėti (<code>+</code>) tekstą su ne teksto reikšme.
<ul>
<li>
Jei norėjote atlikti veiksmą su skaičiais, neužmirškite prieš tai
 tekste esantiems skaičiams pritaikyti <code>int()</code> ar <code>float()</code>.
</li>
<li>
Jei norėjote sujungti dvi tekstines reikšmes, neužmirškite pritaikyti <code>str()</code> netekstinėms reikšmėms.
</li>
</ul>
<pre>
'1234' + 5 # ką čia norima padaryti?
</pre>
Veiksmų sekimas gali padėti suprasti, kokios yra kintamųjų reikšmės klaidos metu.",

"AttributeError: '(.*)' object has no attribute '(.*)'" =>

'Reiškia, kad objektas <code>$1</code> neturi metodo/funkcijos ar savybės/kintamojo <code>$2</code>.
Patikrinkite, ar naudojate tinkamos klasės/tipo objektą ir ar nesupainiojote rašybos (didžiųjų raidžių ar pan). Pvz.:
<pre>
obj = [1, 2]
obj.replace(1, 0) # sąrašai neturi "replace" metodo
</pre>
Parašę <code>print(dir(obj))</code>, galite sužinoti visus to objekto turimus metodus.
Arba naudokite veiksmų sekimą, kad tiksliau nustatymėte klaidos priežastį.
',
                 "TypeError: '.*' object is not subscriptable"
=>
"Duomenų tipo klaida: objektas nėra indeksuojamas (su <code>[  ]</code>).

Parašėte <code>[...]</code> po kintamojo, kuris neturi indeksuojamų elementų.
Tai gali nutikti dėl įv. gramatikos supainiojimų. 
Gal turėjote omeny <code>( )</code> po funkcijos pavadinimo, ar kažką praleidote?
<pre>
print(input[0]) # pabandykite print(input()[0])
</pre>
Kad patikrintumėte reikšmes, galite naudoti veiksmų sekimą.
",
                 "TypeError: string indices must be integers"
=>
"Duomenų tipo klaida: teksto indeksai (simbolių numeriai) gali būti tik sveikieji skaičiai.<br><br>
Tarp indekso nurodymo skliaustų <code>[ ]</code> parašėte ne sveiką skaičių, pvz.:
<pre>
mystring[5/2] # 5/2 nėra sveikas skaičius
</pre>
Galite naudoti veiksmų sekimą, kad matytumėte, kokios buvo kintamųjų reikšmės prieš įvykstant klaidai.
",
                 "SyntaxError: can't assign to (function call|literal)"
=>
'
Bandoma priskirti reikšmę funkcijos iškvietimui arba tekstui, pvz
<pre>
sqrt(4) = x #  sqrt(4) ir taip apskaičiuoja reikšmę, jai nieko priskirt nereik!
</pre>
Gal:
<ul>
<li> norėjote priskirti atvirkščiai <code>x = sqrt(4)</code>. Kairėj priskyrimo pusėj turi būti kintamasis, o dešinėj - kokia nors reikšmė ar išraiška.
</li>
<li>arba norėjote palyginti <code>sqrt(4) == x</code> (reikia dviejų lygybės ženklų)?
</li>
</ul>
',
                 "UnboundLocalError: local variable '(.*)' referenced before assignment"
=>
'
Viduje funkcijos bandote naudoti kintamąjį \"<code>$1</code>\" anksčiau negu jam priskiriate reikšmę.
O gal užmiršote nurodyti, kad tas kintamasis yra globalus?
<pre>
def f():
 print(x)
 x = 5
f()
</pre>
Galite naudoti veiksmų sekimą, kad matytumėte, kokios yra reikšmės prieš įvykstant klaidai.
Daugiau apie šią klaidą (angliškai): 
<a href="http://docs.python.org/3/faq/programming.html#why-am-i-getting-an-unboundlocalerror-when-the-variable-has-a-value">čia</a> bei 
<a href="http://docs.python.org/3/faq/programming.html#what-are-the-rules-for-local-and-global-variables-in-python">čia</a>.
',

"SyntaxError: can't assign to operator"
=>
"
Sintaksės klaida: negalima priskirti reikšmės aritmetiam veiksmui (operacijai).<br><br>
Gal sumaišėte priskyrimo puses? Kairėj priskyrimo pusėj turi būti kintamasis, o dešinėj - kokia nors reikšmė ar išraiška.
<pre>
x + y = z # try z = x + y or x + y == z
</pre>

Arba gal supainiojote priskyrimo veiksmą <code>=</code> su palyginimo veiksmu <code>==</code>?

",

                 "ValueError: could not convert string to float:.*"
=>
"Nepavyko teksto paversti realiu skaičiumi (su kableliu).<br><br>
Gal tekste yra užrašytas ne tik skaičius? Gal ten yra nereikalingų/netinkamų simbolių?
Neužmiškime, kad vietoj kablelio Python naudojamas „<code>.</code>“ (taškas).
<pre>
x = float('314 159') # gal turėta omeny 314.159?
</pre>
Galite naudoti veiksmų sekimą, kad matytumėte, kokios buvo reikšmės prieš įvykstant klaidai.",

                 "TypeError: unorderable types.*"
=>
"
Buvo bandoma palyginti du nepalyginamus objektus (duomenis). 
<br>
Galima palyginti 2 skaičius
<code>5 >= 4.5</code> ar 2 tekstus <code>'food' > 'fish'</code>,
bet jei juos sumaišysime, bus bėda:
<pre>
z = max(5, '6')       # klaida nutinka, kai „max“ viduje atliekamas palyginimo veiksmas <code>&gt;</code>
ardaugiau = max > min # klaida, nes nėra numatyta, kaip palyginti 2 funkcijas (kaip objektus)
</pre>
Galite naudoti  <code>type(..)</code> komandą bei veiksmų sekimą, kad matytumėte, kokio tipo duomenys yra lyginami.",

                 "TypeError: not all arguments converted during string formatting" =>
"Tipo klaida: ne visi formatavimo argumentai panaudoti.<br><br>
Simbolis <code>%</code> naudojamas dviem skirtingais tiklsais:
skaičių dalybos liekanai rasti bei <i>teksto formatavime</i>.
Ši klaida, tikriausiai, reiškia, kad jūs kažką sumaišėte - gal nepavertėte įvestų duomenų skaičiais?

<pre>
if '10' % 5 == 0: # '10' turi būti <code>int</code> tipo
</pre>
Galite naudoti veiksmų sekimą, kad matytumėte, kokios buvo reikšmės prieš įvykstant klaidai.",

"TypeError: (object of type '.*' has no .*\(\)|bad operand type for .*|.* argument must be a .*)"
=>
"
Funkcijai paduoti duomenys yra netinkamo tipo. Tai gali nutikti ir dėl žioplos klaidos, keli pavyzdžiai
<pre>
print(len(input)) # užmiršti <code>()</code> prie <code>input</code>; teisingai būtų: print(len(input()))
print(abs('-5'))  # '-5' reik paversti į <code>int</code> tipą (<code>-5</code>)
</pre>
Galite naudoti veiksmų sekimą, kad matytumėte, kokios buvo reikšmės prieš įvykstant klaidai.
",

                 "TypeError: '.*' object cannot be interpreted as an integer" =>
"Vietoj sveiko skaičiaus buvo netinkamo tipo reikšmė. Tai dažnai nutinka su <code>range()</code> komanda.
<pre>
for i in range(0, '10'): # vietoj '10' (<code>str</code> tipo) reikia <code>int</code> 
</pre>
Galite naudoti veiksmų sekimą, kad matytumėte, kokios buvo reikšmės prieš įvykstant klaidai.
",

"TypeError: an integer is required"
=>
"Duomenų tipo klaida: reikia sveiko skaičiaus.<br><br>
Funkcija tikėjosi gauti sveiką skaičių, tačiau gavo kažkokią kitą reikšmę.
Tai dažnai nutinka naudojant funkciją <code>chr()</code>, kuri pagal numerį grąžina simbolį iš ASCII lentelės:
<pre>
letter = chr('95') # reiktų pirmiau paversti skaičiumi, naudojant int()
</pre>
Galite naudoti veiksmų sekimą, kad suprastumėte, kodėl kilo klaida.
",

"RuntimeError: maximum recursion depth exceeded.*"
=>
'Vykdymo klaida: funkcija per daug kartų iškvietė save rekursiškai.<br><br>
Patikrinkite, gerai veikia rekursijos nutraukimo sąlyga su bet kokiais duomenimis, pvz.:
<pre>
def f(x):
 return x + f(x-1)
print(f(5)) # niekada nesustos!
</pre>
Šią klaidą labai gerai analizuoti su "Veiksmų sekimu".',
                 "IndexError: list assignment index out of range"
=>
'Numeracijos klaida: nurodytas per didelis sąrašo elemento numeris (priskyrimo veiksme).<br><br> 
 Neužmirškite, kad Python\'e numeruojama nuo 0, o paskutinio elemento numeris yra (ilgis-1).
<pre>
aList = ["f", "o"]
aList[2] = "x" # galima priskirti tik  su numeriais: [0] arba [1]
</pre>
Jei norite prie sąrašo pridėti naują reikšmę, naudokite <code>list.append("x")</code>.
<br>
Galite naudot veiksmų sekimą, kad patikrintumėt, koks yra sąrašo/teksto ilgis ar indeksui naudojamo kintamojo reikšmė.
',

"TypeError: slice indices must be integers or None or have an __index__ method"
=>
"Sąrašo/teksto <i>atpjovimas (angl. „slice“)</i>
nurodomas su pradžios, pabaigos numeriais (bei žingsniu):
<code>Sarašas[«pradzia»:«pabaiga»]</code> or <code>Sarašas[«pradzia»:«pabaiga»:«žingsnis»]</code>.
Įsitikinkite, kad visi indeksai viduje <code>[]</code> yra sveiki skaičiai, pvz.:
<pre>
s[1:s[len(s)-1]] # try s[1:len(s)-1]
</pre>
Galite naudoti veiksmų sekimą, kad matytumėte, kokios buvo reikšmės prieš programai „nulūžtant“.",
                 "TypeError: ord\(\) expected .*, but .* found"=>
                "
„<code>ord</code>“ funkcija grąžina simbolio numerį ASCII kodų lentelėje.
Jos argumentai gali būti tik 1 simbolio, o ne ilgesnės teksto eilutės
<pre>
print(ord(3)) # pabandykite ord('3') arba chr(3)?
</pre>
Galite naudoti veiksmų sekimą, kad suprastumėte, kodėl kilo klaida."
                 ,
                 "SyntaxError: '(.*)' .*(outside|not.*in).*loop" =>
'Sintaksės klaida: \'$1\' yra už ciklo ribų.<br><br>
Komanda „<code>$1</code>“ turi prasmę tik  cikle (gal sumaišėte lygiavimą, jeigu ji ne cikle?).
„<code>break</code>“ nutraukia ciklą, o „<code>continue</code>“ peršoka tęsti ciklą nuo tolesnio kartojimo.
',
                 "SyntaxError: 'return' outside function" =>
'Sintaksės klaida: \'return\' už funkcijos ribų.<br><br>
Patikrinkite lygiavimą, ar nepalikote veiksmo, kuris užbaigia funkciją anksčiau negu grąžina reikšmę.
Komanda <code>return</code> gali būti tik funkcijų viduje.
<pre>
def f(x):
  if x > 0: return True
return False           # čia jau ne funkcijoje (nes neatitraukta nuo krašto)
</pre>
',
                 'TypeError: can only concatenate list \(not .*\) to list'
=>
'Duomenų tipo klaida: su sąrašu sujungti galima tik kitą sąrašą<.br>
Sąrašų padidinimas Python\'e gali vykti 2 būdais:
<pre>
L = [1, 2]
print(L + [3]) # L lieka [1, 2]
L.append(3)    # L padidėja
</pre>
Ši klaida reiškia, kad jūs naudojote  <code>+</code> bet antras kintamasis nėra sąrašas. 
Galite naudoti veiksmų sekimą, kad geriau suprastumėte, kas nutiko.
'
);
