#!/usr/bin/env node

// Needs node-fetch to be installed globally to work
import fetch from "node-fetch";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const GENERAL_SOURCE =
  "https://data.senat.fr/data/senateurs/ODSEN_GENERAL.json";
const MAIN_SOURCE = "https://www.senat.fr/api-senat/senateurs.json";
const TARGET = `${__dirname}/senateurs.json`;

const initializeData = () => {
  let data = fs.readFileSync(TARGET);
  return JSON.parse(data).map((departement) => ({
    ...departement,
    sen: [],
  }));
};

const downloadData = async () => {
  let res = await fetch(MAIN_SOURCE);
  const mainData = await res.json();

  res = await fetch(GENERAL_SOURCE);
  const generalData = await res.json();

  return mainData.map((senateur) => ({
    ...senateur,
    ...(generalData.results.find((s) => s.Matricule === senateur.matricule) ||
      {}),
  }));
};

const formatData = (data) => {
  const departements = initializeData();
  return departements.map((departement) => {
    departement.sen = data
      .filter((senateur) =>
        departement.id === "99"
          ? senateur.circonscription.code.startsWith("99")
          : senateur.circonscription.code.toUpperCase() ===
            departement.id.toUpperCase(),
      )
      .map((senateur) => ({
        id: senateur.matricule,
        s: senateur.Qualite === "Mme" ? "F" : "M",
        p: senateur.prenom,
        n: senateur.nom,
        g: senateur.groupe.code,
        gl: senateur.groupe.libelle,
        e: senateur.Courrier_electronique || "",
        t: senateur.twitter ? senateur.twitter.split("/").pop() : "",
        f: senateur.facebook,
        dn: senateur.Date_naissance,
      }));

    return departement;
  });
};

const saveFile = (data) => {
  fs.writeFileSync(TARGET, JSON.stringify(data));
};

const generateJsonData = async () => {
  try {
    console.log("Fetching data...");
    let data = await downloadData();
    console.log("Formatting data...");
    data = formatData(data);
    console.log("Saving data to file: " + TARGET);
    saveFile(data);
    console.log("Done!");
  } catch (e) {
    console.error(e);
  }
};

export default generateJsonData();
