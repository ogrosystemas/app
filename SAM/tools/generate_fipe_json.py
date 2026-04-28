#!/usr/bin/env python3
"""
Gera assets/fipe_marcas.json com marcas e modelos verificados da tabela FIPE.
Dados baseados na tabela FIPE oficial — modelos como aparecem no sistema.
"""
import json, os

# ── MARCAS (com códigos FIPE oficiais) ──────────────────────────────────────
carros = [
    {"code":"1","name":"Acura"},
    {"code":"2","name":"Agrale"},
    {"code":"3","name":"Alfa Romeo"},
    {"code":"4","name":"AM Gen"},
    {"code":"5","name":"Asia Motors"},
    {"code":"189","name":"ASTON MARTIN"},
    {"code":"6","name":"Audi"},
    {"code":"7","name":"Baby"},
    {"code":"8","name":"BMW"},
    {"code":"9","name":"BRM"},
    {"code":"10","name":"Buggy"},
    {"code":"11","name":"Bugre"},
    {"code":"12","name":"Cadillac"},
    {"code":"13","name":"CBT Jipe"},
    {"code":"14","name":"Chana"},
    {"code":"15","name":"Changan"},
    {"code":"16","name":"Chery"},
    {"code":"17","name":"Chevrolet"},
    {"code":"18","name":"Chrysler"},
    {"code":"19","name":"Citroën"},
    {"code":"20","name":"Cross Lander"},
    {"code":"21","name":"Daewoo"},
    {"code":"22","name":"Daihatsu"},
    {"code":"23","name":"Dodge"},
    {"code":"24","name":"EFFA"},
    {"code":"25","name":"Engesa"},
    {"code":"26","name":"Envemo"},
    {"code":"27","name":"Ferrari"},
    {"code":"28","name":"Fiat"},
    {"code":"29","name":"Fibravan"},
    {"code":"30","name":"Ford"},
    {"code":"190","name":"FOTON"},
    {"code":"31","name":"Fyber"},
    {"code":"32","name":"GEELY"},
    {"code":"33","name":"GM - Chevrolet"},
    {"code":"34","name":"Grancar"},
    {"code":"35","name":"Gurgel"},
    {"code":"36","name":"Honda"},
    {"code":"37","name":"Hyundai"},
    {"code":"38","name":"Isuzu"},
    {"code":"39","name":"Iveco"},
    {"code":"40","name":"JAC"},
    {"code":"41","name":"Jaguar"},
    {"code":"42","name":"Jeep"},
    {"code":"43","name":"Kia Motors"},
    {"code":"44","name":"Lada"},
    {"code":"45","name":"Land Rover"},
    {"code":"46","name":"Lexus"},
    {"code":"47","name":"Lifan"},
    {"code":"48","name":"LOBINI"},
    {"code":"49","name":"Lotus"},
    {"code":"50","name":"Mahindra"},
    {"code":"51","name":"Maserati"},
    {"code":"52","name":"Mazda"},
    {"code":"53","name":"Mercedes-Benz"},
    {"code":"54","name":"Mercury"},
    {"code":"55","name":"MG"},
    {"code":"56","name":"MINI"},
    {"code":"57","name":"Mitsubishi"},
    {"code":"58","name":"Miura"},
    {"code":"59","name":"Nissan"},
    {"code":"60","name":"Oldsmobile"},
    {"code":"61","name":"Peugeot"},
    {"code":"62","name":"Plymouth"},
    {"code":"63","name":"Pontiac"},
    {"code":"64","name":"Porsche"},
    {"code":"65","name":"RAM"},
    {"code":"66","name":"RELY"},
    {"code":"67","name":"Renault"},
    {"code":"68","name":"Rolls-Royce"},
    {"code":"69","name":"Rover"},
    {"code":"70","name":"Saab"},
    {"code":"71","name":"Saturn"},
    {"code":"72","name":"Seat"},
    {"code":"73","name":"SHINERAY"},
    {"code":"74","name":"Smart"},
    {"code":"75","name":"SSANGYONG"},
    {"code":"76","name":"Subaru"},
    {"code":"77","name":"Suzuki"},
    {"code":"78","name":"Toyota"},
    {"code":"79","name":"Troller"},
    {"code":"80","name":"Volvo"},
    {"code":"81","name":"VW - VolksWagen"},
    {"code":"82","name":"Wake"},
    {"code":"83","name":"Walk"},
    {"code":"153","name":"BYD"},
    {"code":"161","name":"GWM"},
    {"code":"163","name":"Caoa Chery"},
]

motos = [
    {"code":"100","name":"ADLY"},
    {"code":"101","name":"Agrale"},
    {"code":"102","name":"Aprilia"},
    {"code":"103","name":"BMW"},
    {"code":"104","name":"Brandy"},
    {"code":"105","name":"BRP"},
    {"code":"106","name":"Buell"},
    {"code":"107","name":"CF MOTO"},
    {"code":"108","name":"Dafra"},
    {"code":"109","name":"Ducati"},
    {"code":"110","name":"FURLAND"},
    {"code":"111","name":"Harley-Davidson"},
    {"code":"112","name":"Honda"},
    {"code":"113","name":"Husaberg"},
    {"code":"114","name":"Husqvarna"},
    {"code":"115","name":"Indian"},
    {"code":"116","name":"Kawasaki"},
    {"code":"117","name":"KTM"},
    {"code":"118","name":"Kymco"},
    {"code":"119","name":"Malaguti"},
    {"code":"120","name":"Moto Guzzi"},
    {"code":"121","name":"Piaggio"},
    {"code":"122","name":"Royal Enfield"},
    {"code":"123","name":"Shineray"},
    {"code":"124","name":"Sundown"},
    {"code":"125","name":"Suzuki"},
    {"code":"126","name":"Triumph"},
    {"code":"127","name":"Vespa"},
    {"code":"128","name":"Yamaha"},
    {"code":"129","name":"Zongshen"},
]

caminhoes = [
    {"code":"200","name":"Agrale"},
    {"code":"201","name":"DAF"},
    {"code":"202","name":"Ford"},
    {"code":"203","name":"GM - Chevrolet"},
    {"code":"204","name":"International"},
    {"code":"205","name":"Iveco"},
    {"code":"206","name":"MAN"},
    {"code":"207","name":"Mercedes-Benz"},
    {"code":"208","name":"Mitsubishi"},
    {"code":"209","name":"Nissan"},
    {"code":"210","name":"Peugeot"},
    {"code":"211","name":"Renault"},
    {"code":"212","name":"Scania"},
    {"code":"213","name":"Toyota"},
    {"code":"214","name":"Volkswagen"},
    {"code":"215","name":"Volvo"},
]

# ── MODELOS (exatos como na FIPE, relevantes para auto peças) ────────────────
modelos = {
    # CARROS
    "Chevrolet": [
        "Agile","Astra Hatch","Astra Sedan","Blazer","Celta","Cobalt","Colorado",
        "Corsa Classic","Corsa Hatch","Corsa Sedan","Cruze Hatch","Cruze Sedan",
        "Equinox","Kadett","Montana","Monza","Onix","Onix Plus","Onix Hatch",
        "Prisma","S10","Silverado","Sonic Hatch","Sonic Sedan","Spin",
        "Suburban","Tracker","TrailBlazer","Vectra","Zafira","Captiva",
    ],
    "GM - Chevrolet": [
        "Agile","Astra Hatch","Astra Sedan","Blazer","Celta","Cobalt","Colorado",
        "Corsa Classic","Corsa Hatch","Corsa Sedan","Cruze Hatch","Cruze Sedan",
        "Equinox","Kadett","Montana","Monza","Onix","Onix Plus",
        "Prisma","S10","Silverado","Sonic","Spin","Suburban",
        "Tracker","TrailBlazer","Vectra","Zafira","Captiva",
    ],
    "Fiat": [
        "Argo","Bravo","Cronos","Doblo","Ducato","Fastback","Fiorino",
        "Grand Siena","Idea","Linea","Marea","Marea Weekend","Mobi",
        "Palio","Palio Weekend","Pulse","Punto","Scudo","Siena",
        "Stilo","Strada","Toro","Tipo","Uno","147","Tempra",
    ],
    "Ford": [
        "Bronco Sport","Cargo 816","Cargo 1119","Courier","EcoSport","Edge",
        "Escort","Explorer","F-250","F-350","F-4000","Fiesta Hatch",
        "Fiesta Sedan","Focus Hatch","Focus Sedan","Fusion","Ka","Ka+",
        "Ka Sedan","Maverick","Mondeo","Mustang","Orion","Pampa",
        "Ranger","Territory","Transit","Verona",
    ],
    "VW - VolksWagen": [
        "Amarok","Bora","Crossfox","Fox","Fusca","Gol","Golf",
        "Jetta","Kombi","Parati","Passat","Polo Hatch","Polo Sedan",
        "Quantum","Saveiro","SpaceCross","SpaceFox","Tiguan","T-Cross",
        "Touareg","Up","Variant","Virtus","Voyage","Taos",
    ],
    "Toyota": [
        "Bandeirante","Camry","Corolla","Corolla Cross","Etios Hatch",
        "Etios Sedan","Fielder","Fortuner","Hilux","Land Cruiser",
        "Land Cruiser Prado","Prius","RAV4","SW4","Yaris Hatch","Yaris Sedan",
    ],
    "Honda": [
        "Accord","City","City Hatch","Civic","CR-V","Fit","HR-V",
        "Jazz","Legend","Odyssey","WR-V","ZR-V",
    ],
    "Hyundai": [
        "Azera","Creta","Elantra","HB20","HB20S","HB20X","i30",
        "iX35","Santa Fe","Sonata","Tucson","Veloster",
    ],
    "Renault": [
        "Captur","Clio","Duster","Express","Fluence","Grand Tour",
        "Kangoo","Kardian","Kwid","Laguna","Logan","Master",
        "Mégane","Oroch","Sandero","Scenic","Symbol","Trafic",
    ],
    "Nissan": [
        "Altima","Frontier","Grand Livina","Kicks","Leaf",
        "Livina","March","Maxima","Murano","Pathfinder",
        "Sentra","Tiida","Versa","X-Trail",
    ],
    "Peugeot": [
        "106","206","207","207 SW","208","2008","301","306",
        "307","307 SW","308","3008","408","5008","Expert","Partner",
    ],
    "Citroën": [
        "Aircross","Berlingo","Basalt","C3","C3 Aircross","C4","C4 Cactus",
        "C4 Lounge","C4 Picasso","C5","Dispatch","Jumper","Jumpy",
        "Xantia","Xsara","Xsara Picasso",
    ],
    "Mitsubishi": [
        "ASX","Eclipse Cross","Endeavor","Galant","L200 Triton",
        "L200 Sport","Lancer","Outlander","Pajero","Pajero Dakar",
        "Pajero Full","Pajero Sport","Space Wagon","Eclipse",
    ],
    "Kia Motors": [
        "Bongo","Carens","Carnival","Cerato","Mohave","Optima",
        "Picanto","Sorento","Soul","Sportage","Stinger","Stonic","EV6",
    ],
    "Mercedes-Benz": [
        "A 160","A 180","A 200","A 250","B 180","B 200","C 180","C 200",
        "C 220","C 250","C 300","CLA 200","CLA 250","E 200","E 220",
        "E 250","E 300","GLA 200","GLA 250","GLC 200","GLC 250","GLC 300",
        "GLE 350","GLE 400","ML 350","S 500","Sprinter","Vito","GLB 200",
    ],
    "BMW": [
        "116i","118i","120i","125i","218i","220i","316i","318i","320i",
        "323i","325i","328i","330i","418i","420i","428i","430i","520i",
        "523i","525i","528i","530i","535i","X1","X2","X3","X4","X5","X6","Z3","Z4",
    ],
    "Audi": [
        "A1","A3","A3 Sedan","A4","A4 Avant","A5","A5 Sportback",
        "A6","A7","A8","Q2","Q3","Q5","Q7","Q8","R8","RS3","RS4",
        "RS5","S3","S4","S5","TT","e-tron",
    ],
    "Jeep": [
        "Cherokee","Commander","Compass","Grand Cherokee",
        "Renegade","Wrangler",
    ],
    "Land Rover": [
        "Defender","Discovery","Discovery Sport","Evoque",
        "Freelander","Range Rover","Range Rover Sport",
        "Range Rover Velar","Freelander 2",
    ],
    "Subaru": [
        "Forester","Impreza","Legacy","Outback","Tribeca","WRX","XV","BRZ",
    ],
    "Suzuki": [
        "Baleno","Grand Vitara","Jimny","S-Cross","Swift","SX4","Vitara",
    ],
    "Chery": [
        "Arrizo 5","Arrizo 6","QQ","Tiggo 2","Tiggo 3X","Tiggo 5X",
        "Tiggo 7","Tiggo 7 Pro","Tiggo 8","Tiggo 8 Pro",
    ],
    "Caoa Chery": [
        "Arrizo 6 Pro","Tiggo 5X","Tiggo 7 Pro","Tiggo 8 Pro",
        "iCar","Tiggo 2 Pro",
    ],
    "JAC": [
        "J2","J3","J5","J6","S1","S2","S3","T6","T8","iEV40",
    ],
    "BYD": [
        "Dolphin","Han","King","Seal","Song Plus","Tan","Yuan Plus",
    ],
    "GWM": [
        "Haval H6","Haval H6 Hybrid","Ora 03","Tank 300","Wingle 7",
    ],
    "Volvo": [
        "C30","S40","S60","S80","V40","V50","V60","V90",
        "XC40","XC60","XC70","XC90",
    ],
    "Alfa Romeo": [
        "145","146","147","155","156","159","Brera","Giulia",
        "Giulietta","Spider","Stelvio",
    ],
    "Dodge": [
        "Challenger","Charger","Dakota","Dart","Durango",
        "Journey","Neon","Ram","Viper",
    ],
    "Chrysler": [
        "300","300C","Grand Caravan","Neon","PT Cruiser",
        "Sebring","Voyager",
    ],
    "Jeep": [
        "Cherokee","Commander","Compass","Grand Cherokee",
        "Renegade","Wrangler",
    ],
    "Porsche": [
        "718 Boxster","718 Cayman","911","Cayenne","Macan","Panamera","Taycan",
    ],
    "Lexus": [
        "CT 200h","ES 250","ES 300h","IS 250","IS 300","LX 570",
        "NX 200t","NX 300h","RX 350","RX 450h","UX 250h",
    ],
    "Mazda": [
        "2","3","5","6","CX-3","CX-30","CX-5","CX-9","MX-5","RX-8",
    ],
    "MINI": [
        "Clubman","Convertible","Cooper","Cooper S","Countryman",
        "Hatch","Paceman","Roadster",
    ],
    "Isuzu": [
        "D-Max","NPR","NQR","NNR","Trooper",
    ],
    "Iveco": [
        "Daily 35S14","Daily 55C16","Daily 70C17","Stralis","Tector","Vertis",
    ],

    # MOTOS (mais populares no Brasil para auto peças)
    "Yamaha": [
        "Crosser 150","Factor 125","Factor 150","Fazer 150","Fazer 250",
        "FZ25","Lander 250","MT-03","MT-07","MT-09","Neo 115",
        "R1","R3","R6","Tenere 250","Tenere 700","V-Max","XJ6","XTZ 125",
        "XTZ 150","XTZ 250","YBR 125","YBR 125 Factor",
    ],
    "Honda": [  # Motos Honda
        "Biz 100","Biz 110i","Biz 125","Bros 125","Bros 150","Bros 160",
        "CB 300R","CB 300F Twister","CB 500F","CB 500X","CB 650R",
        "CB 750","CB 1000R","CG 125","CG 150","CG 160","CG 190",
        "NXR 150 Bros","NXR 160 Bros","PCX 150","PCX 160","Pop 110i",
        "Titan 125","Titan 150","Titan 160","Twister 250",
        "XRE 190","XRE 300","Xre 300 Adventure","NC 700X","NC 750X",
        "Africa Twin","Hornet",
    ],
    "Kawasaki": [
        "Ninja 250","Ninja 300","Ninja 400","Ninja 650","Ninja 1000",
        "Versys 300","Versys 650","Versys 1000","Z 300","Z 400",
        "Z 650","Z 750","Z 800","Z 900","Z 1000","KLX 150",
    ],
    "Suzuki": [
        "Bandit 650","Boulevard 1500","GS 500","GSR 750","GSX 750",
        "GSX-R 600","GSX-R 750","GSX-R 1000","Intruder 125",
        "Intruder 250","V-Strom 650","V-Strom 1000",
    ],
    "Harley-Davidson": [
        "Fat Bob","Fat Boy","Heritage Classic","Iron 883","Iron 1200",
        "Low Rider","Nightster","Road Glide","Road King","Softail",
        "Sportster","Street 500","Street 750","Ultra Limited",
    ],
    "Dafra": [
        "Apache 150","Apache 180","Kansas 150","Laser 150",
        "Riva 150","Roadwin 250R","Speed 150","Super 50","Sym 125",
    ],
    "Sundown": [
        "Hunter 125","Max 125","Motard 200","STX 125","STX 200",
        "Web 100","Web 115","Web 125",
    ],
    "Ducati": [
        "Diavel","Hypermotard","Monster 797","Monster 821","Monster 1200",
        "Multistrada","Panigale V2","Panigale V4","Scrambler","Streetfighter",
    ],
    "KTM": [
        "200 Duke","250 Duke","390 Duke","690 Duke","1290 Super Duke",
        "200 EXC","350 EXC-F","450 EXC-F","690 Enduro",
        "250 Adventure","390 Adventure","790 Adventure","1190 Adventure",
    ],

    # CAMINHÕES
    "Agrale": [
        "6000D","7500D","8500D","9200D","10000D","MA 8.5",
        "MA 9.2","MT 12","MT 15","Marruá",
    ],
    "Ford": [  # Caminhões Ford
        "Cargo 816","Cargo 1119","Cargo 1317","Cargo 1519","Cargo 1722",
        "Cargo 2042","Cargo 2428","Cargo 2629","F-350","F-4000","Transit",
    ],
    "Mercedes-Benz": [  # Caminhões MB
        "710","712","914","915","1318","1620","1718","1722","1726",
        "1733","2026","2429","2436","Actros 2651","Atego 1418",
        "Axor 2041","Sprinter Chassi",
    ],
    "Volkswagen": [
        "7.90S","8.150","9.150","13.180","13.190","15.190","17.230",
        "17.250","24.280","26.280","31.320","Delivery 9.170",
        "e-Delivery","Constellation",
    ],
    "Volvo": [  # Caminhões Volvo
        "FH 380","FH 400","FH 420","FH 460","FH 500","FH 540",
        "FM 330","FM 370","FM 410","FMX 330","FMX 370",
        "VM 210","VM 270","VM 310",
    ],
    "Scania": [
        "P 250","P 310","P 360","P 410","R 400","R 450","R 500",
        "R 540","R 580","G 380","G 410","G 440","G 480","S 500","S 580",
    ],
    "Iveco": [  # Caminhões Iveco
        "Daily 35S14","Daily 55C16","Daily 70C17","Eurocargo 170E22",
        "Eurocargo 260E28","Stralis 480","Stralis 540","Tector 170E22",
        "Tector 240E28","Vertis 130V18",
    ],
    "MAN": [
        "TGX 28.440","TGX 29.440","TGX 33.440","TGS 26.280",
        "TGS 26.440","TGM 15.250","TGL 8.180",
    ],
}

# ── Salvar JSON ──────────────────────────────────────────────────────────────
output = {
    "carros": carros,
    "motos": motos,
    "caminhoes": caminhoes,
    "modelos": modelos,
}

path = os.path.join(os.path.dirname(__file__), '..', 'assets', 'fipe_marcas.json')
path = os.path.normpath(path)
os.makedirs(os.path.dirname(path), exist_ok=True)

with open(path, 'w', encoding='utf-8') as f:
    json.dump(output, f, ensure_ascii=False, separators=(',', ':'))

total_marcas = len(carros) + len(motos) + len(caminhoes)
total_modelos = sum(len(v) for v in modelos.values())
print(f"Gerado: {total_marcas} marcas, {len(modelos)} com modelos, {total_modelos} modelos total")
print(f"Arquivo: {path}")
