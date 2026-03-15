/* =============================================
   STEIRER PELLETS – PLZ Datenbank Steiermark
   Alle relevanten PLZ + Ort + Region
   ============================================= */

const PLZ_DATA = {
  // === Graz & Graz-Umgebung ===
  "8010": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8011": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8020": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8036": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8041": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8042": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8043": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8044": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8045": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8046": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8047": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8051": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8052": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8053": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8054": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8055": { ort: "Graz",               region: "Graz",            slug: "graz"              },
  "8061": { ort: "St. Radegund",       region: "Graz-Umgebung",   slug: "graz-umgebung"     },
  "8062": { ort: "Kumberg",            region: "Graz-Umgebung",   slug: "graz-umgebung"     },
  "8063": { ort: "Eggersdorf",         region: "Graz-Umgebung",   slug: "graz-umgebung"     },
  "8071": { ort: "Hausmannstätten",    region: "Graz-Umgebung",   slug: "graz-umgebung"     },
  "8072": { ort: "Fernitz",            region: "Graz-Umgebung",   slug: "graz-umgebung"     },
  "8073": { ort: "Feldkirchen bei Graz", region: "Graz-Umgebung", slug: "graz-umgebung"     },
  "8074": { ort: "Raaba",              region: "Graz-Umgebung",   slug: "graz-umgebung"     },
  "8075": { ort: "Hart bei Graz",      region: "Graz-Umgebung",   slug: "graz-umgebung"     },
  "8076": { ort: "Vasoldsberg",        region: "Graz-Umgebung",   slug: "graz-umgebung"     },
  "8081": { ort: "Heiligenkreuz am Waasen", region: "Graz-Umgebung", slug: "graz-umgebung"  },
  "8082": { ort: "Kirchbach-Zerlach",  region: "Graz-Umgebung",   slug: "graz-umgebung"     },
  "8083": { ort: "St. Stefan im Rosental", region: "Graz-Umgebung", slug: "graz-umgebung"   },

  // === Voitsberg & Köflach (Heimregion) ===
  "8570": { ort: "Voitsberg",          region: "Voitsberg",       slug: "voitsberg"         },
  "8572": { ort: "Bärnbach",           region: "Voitsberg",       slug: "voitsberg"         },
  "8573": { ort: "Söding",             region: "Voitsberg",       slug: "voitsberg"         },
  "8580": { ort: "Köflach",            region: "Voitsberg",       slug: "koeflach"          },
  "8582": { ort: "Rosental an der Kainach", region: "Voitsberg",  slug: "voitsberg"         },
  "8583": { ort: "Edelschrott",        region: "Voitsberg",       slug: "voitsberg"         },
  "8591": { ort: "Maria Lankowitz",    region: "Voitsberg",       slug: "voitsberg"         },
  "8592": { ort: "Söding-St. Johann",  region: "Voitsberg",       slug: "voitsberg"         },

  // === Weiz ===
  "8160": { ort: "Weiz",               region: "Weiz",            slug: "weiz"              },
  "8162": { ort: "Passail",            region: "Weiz",            slug: "weiz"              },
  "8163": { ort: "Fladnitz im Raabtal", region: "Weiz",           slug: "weiz"              },
  "8164": { ort: "Edelsdorf",          region: "Weiz",            slug: "weiz"              },
  "8181": { ort: "St. Ruprecht an der Raab", region: "Weiz",      slug: "weiz"              },
  "8182": { ort: "Puch bei Weiz",      region: "Weiz",            slug: "weiz"              },
  "8183": { ort: "Gleisdorf",          region: "Weiz",            slug: "weiz"              },
  "8190": { ort: "Birkfeld",           region: "Weiz",            slug: "weiz"              },
  "8192": { ort: "Anger",              region: "Weiz",            slug: "weiz"              },

  // === Leoben ===
  "8700": { ort: "Leoben",             region: "Leoben",          slug: "leoben"            },
  "8712": { ort: "Niklasdorf",         region: "Leoben",          slug: "leoben"            },
  "8713": { ort: "St. Stefan ob Leoben", region: "Leoben",        slug: "leoben"            },
  "8720": { ort: "Knittelfeld",        region: "Murtal",          slug: "murtal"            },
  "8721": { ort: "Zeltweg",            region: "Murtal",          slug: "murtal"            },
  "8724": { ort: "Spielberg",          region: "Murtal",          slug: "murtal"            },
  "8725": { ort: "Judenburg",          region: "Murtal",          slug: "murtal"            },

  // === Bruck-Mürzzuschlag ===
  "8600": { ort: "Bruck an der Mur",   region: "Bruck-Mürzzuschlag", slug: "bruck-mur"     },
  "8605": { ort: "Kapfenberg",         region: "Bruck-Mürzzuschlag", slug: "bruck-mur"     },
  "8680": { ort: "Mürzzuschlag",       region: "Bruck-Mürzzuschlag", slug: "muerzzuschlag"  },
  "8684": { ort: "Langenwang",         region: "Bruck-Mürzzuschlag", slug: "muerzzuschlag"  },
  "8685": { ort: "Veitsch",            region: "Bruck-Mürzzuschlag", slug: "muerzzuschlag"  },

  // === Leibnitz ===
  "8430": { ort: "Leibnitz",           region: "Leibnitz",        slug: "leibnitz"          },
  "8435": { ort: "Wagna",              region: "Leibnitz",        slug: "leibnitz"          },
  "8443": { ort: "Gleinstätten",       region: "Leibnitz",        slug: "leibnitz"          },
  "8444": { ort: "Wildon",             region: "Leibnitz",        slug: "leibnitz"          },
  "8451": { ort: "Heimschuh",          region: "Leibnitz",        slug: "leibnitz"          },
  "8452": { ort: "Gralla",             region: "Leibnitz",        slug: "leibnitz"          },
  "8455": { ort: "Eichberg",           region: "Leibnitz",        slug: "leibnitz"          },
  "8461": { ort: "Ehrenhausen",        region: "Leibnitz",        slug: "leibnitz"          },
  "8462": { ort: "Gamlitz",            region: "Leibnitz",        slug: "leibnitz"          },
  "8463": { ort: "Leutschach",         region: "Leibnitz",        slug: "leibnitz"          },

  // === Deutschlandsberg ===
  "8530": { ort: "Deutschlandsberg",   region: "Deutschlandsberg", slug: "deutschlandsberg" },
  "8541": { ort: "Schwanberg",         region: "Deutschlandsberg", slug: "deutschlandsberg" },
  "8542": { ort: "Amtmann",            region: "Deutschlandsberg", slug: "deutschlandsberg" },
  "8543": { ort: "Frauental an der Laßnitz", region: "Deutschlandsberg", slug: "deutschlandsberg" },

  // === Hartberg-Fürstenfeld ===
  "8230": { ort: "Hartberg",           region: "Hartberg-Fürstenfeld", slug: "hartberg"     },
  "8250": { ort: "Vorau",              region: "Hartberg-Fürstenfeld", slug: "hartberg"     },
  "8280": { ort: "Fürstenfeld",        region: "Hartberg-Fürstenfeld", slug: "fuerstenfeld" },
  "8282": { ort: "Bad Waltersdorf",    region: "Hartberg-Fürstenfeld", slug: "fuerstenfeld" },
  "8283": { ort: "Bad Blumau",         region: "Hartberg-Fürstenfeld", slug: "fuerstenfeld" },

  // === Murau ===
  "8850": { ort: "Murau",              region: "Murau",           slug: "murau"             },
  "8851": { ort: "St. Georgen ob Murau", region: "Murau",         slug: "murau"             },
  "8852": { ort: "Stolzalpe",          region: "Murau",           slug: "murau"             },
  "8861": { ort: "St. Peter am Kammersberg", region: "Murau",     slug: "murau"             },
  "8862": { ort: "Stadl an der Mur",   region: "Murau",           slug: "murau"             },

  // === Radkersburg / Südoststeiermark ===
  "8490": { ort: "Bad Radkersburg",    region: "Südoststeiermark", slug: "suedoststeiermark" },
  "8492": { ort: "Halbenrain",         region: "Südoststeiermark", slug: "suedoststeiermark" },
  "8493": { ort: "Klöch",              region: "Südoststeiermark", slug: "suedoststeiermark" },
  "8330": { ort: "Feldbach",           region: "Südoststeiermark", slug: "suedoststeiermark" },
  "8333": { ort: "Riegersburg",        region: "Südoststeiermark", slug: "suedoststeiermark" },
  "8380": { ort: "Jennersdorf",        region: "Südoststeiermark", slug: "suedoststeiermark" },

  // === Judenburg / Murau ===
  "8750": { ort: "Judenburg",          region: "Murtal",          slug: "murtal"            },
  "8753": { ort: "Fohnsdorf",          region: "Murtal",          slug: "murtal"            },
  "8755": { ort: "St. Peter ob Judenburg", region: "Murtal",      slug: "murtal"            },
  "8770": { ort: "St. Michael in Obersteiermark", region: "Leoben", slug: "leoben"          },
  "8786": { ort: "Rottenmann",         region: "Liezen",          slug: "liezen"            },
  "8790": { ort: "Eisenerz",           region: "Leoben",          slug: "leoben"            },

  // === Liezen ===
  "8940": { ort: "Liezen",             region: "Liezen",          slug: "liezen"            },
  "8942": { ort: "Wörschach",          region: "Liezen",          slug: "liezen"            },
  "8943": { ort: "Aigen im Ennstal",   region: "Liezen",          slug: "liezen"            },
  "8950": { ort: "Stainach-Pürgg",     region: "Liezen",          slug: "liezen"            },
  "8951": { ort: "Stainach",           region: "Liezen",          slug: "liezen"            },
  "8960": { ort: "Schladming",         region: "Liezen",          slug: "liezen"            },
  "8961": { ort: "Haus im Ennstal",    region: "Liezen",          slug: "liezen"            },
  "8962": { ort: "Gröbming",           region: "Liezen",          slug: "liezen"            },
  "8970": { ort: "Schladming",         region: "Liezen",          slug: "liezen"            },
  "8971": { ort: "Bad Mitterndorf",    region: "Liezen",          slug: "liezen"            },
  "8990": { ort: "Bad Aussee",         region: "Liezen",          slug: "liezen"            },
  "8992": { ort: "Altaussee",          region: "Liezen",          slug: "liezen"            },
  "8993": { ort: "Grundlsee",          region: "Liezen",          slug: "liezen"            },

  // === Klagenfurt / Kärnten (Anlieferung möglich) ===
  "9020": { ort: "Klagenfurt",         region: "Kärnten",         slug: "kaernten"          },
  "9021": { ort: "Klagenfurt",         region: "Kärnten",         slug: "kaernten"          },
  "9100": { ort: "Völkermarkt",        region: "Kärnten",         slug: "kaernten"          },

  // === Wien & NÖ (Lieferung auf Anfrage) ===
  "1010": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1020": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1030": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1100": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1110": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1120": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1130": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1140": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1150": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1160": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1170": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1180": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1190": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1200": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1210": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1220": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "1230": { ort: "Wien",               region: "Wien",            slug: "wien"              },
  "3100": { ort: "St. Pölten",         region: "Niederösterreich", slug: "niederoesterreich" },
  "2700": { ort: "Wiener Neustadt",    region: "Niederösterreich", slug: "niederoesterreich" },
};

// PLZ lookup
function lookupPLZ(plz) {
  return PLZ_DATA[plz] || null;
}

// Alle PLZ die mit einem String beginnen (für Dropdown)
function searchPLZ(query) {
  if (!query || query.length < 2) return [];
  return Object.entries(PLZ_DATA)
    .filter(([plz]) => plz.startsWith(query))
    .slice(0, 6)
    .map(([plz, data]) => ({ plz, ...data }));
}

// Slug-basierte Seiten-URL
function getLandingUrl(plzOrSlug) {
  const data = PLZ_DATA[plzOrSlug];
  const slug = data ? data.slug : plzOrSlug;
  const staticRegionPages = new Set([
    'bruck-mur',
    'deutschlandsberg',
    'fuerstenfeld',
    'graz',
    'graz-umgebung',
    'hartberg',
    'koeflach',
    'leibnitz',
    'leoben',
    'liezen',
    'muerzzuschlag',
    'murau',
    'murtal',
    'suedoststeiermark',
    'voitsberg',
    'weiz'
  ]);
  if (staticRegionPages.has(slug)) {
    return `region/${slug}.html`;
  }
  return `region/index.html?region=${encodeURIComponent(slug)}`;
}
