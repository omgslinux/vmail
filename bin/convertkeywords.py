#!/usr/bin/env python3
import os
import sys

def convert_courier_indexed_list(path):
    path = os.path.abspath(path)
    k_list_path = os.path.join(path, "courierimapkeywords", ":list")
    cur_path = os.path.join(path, "cur")

    if not os.path.isfile(k_list_path) or not os.path.isdir(cur_path):
        return

    print(f"\n[*] Procesando formato indexado en: {path}")

    tags_definition = []
    msg_to_tag_indices = {}
    reading_files = False

    # 1. Parsear el archivo :list
    with open(k_list_path, "r") as f:
        for line in f:
            line = line.strip()
            if not line:
                reading_files = True
                continue

            if not reading_files:
                # Estamos en la cabecera (lista de nombres de etiquetas)
                tags_definition.append(line)
            else:
                # Estamos en la lista de archivos "nombre:indice indice"
                if ":" in line:
                    fname, indices_str = line.split(":", 1)
                    indices = [int(i) for i in indices_str.split() if i.isdigit()]
                    if indices:
                        # Extraer parte canónica (antes de :2, si existiera en el nombre)
                        base_id = fname.split(":2,")[0]
                        msg_to_tag_indices[base_id] = indices

    if not tags_definition:
        return

    # 2. Generar dovecot-keywords (formato: "0 tag0")
    with open(os.path.join(path, "dovecot-keywords"), "w") as f:
        for i, tag in enumerate(tags_definition[:26]):
            f.write(f"{i} {tag}\n")
    print(f"    [+] dovecot-keywords creado con {len(tags_definition)} tags.")

    # 3. Renombrar archivos en cur/
    count = 0
    for mfname in os.listdir(cur_path):
        m_base_id = mfname.split(":2,")[0]

        if m_base_id in msg_to_tag_indices:
            indices = msg_to_tag_indices[m_base_id]
            new_flags = ""
            for idx in indices:
                if idx < 26:
                    # Mapear índice numérico a letra (0->a, 1->b...)
                    new_flags += chr(ord('a') + idx)

            if new_flags and ":2," in mfname:
                base, info = mfname.split(":2,", 1)
                # Combinar flags de sistema (S, R) con nuevas letras de keywords
                combined = "".join(sorted(list(set(info + new_flags))))
                new_fname = f"{base}:2,{combined}"

                if mfname != new_fname:
                    os.rename(os.path.join(cur_path, mfname), os.path.join(cur_path, new_fname))
                    count += 1

    print(f"    [+] {count} mensajes actualizados con éxito.")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Uso: python3 script.py /ruta/al/Maildir")
        sys.exit(1)

    target = sys.argv[1]
    convert_courier_indexed_list(target)
    # Recursividad para subcarpetas que tengan su propio :list
    for root, dirs, files in os.walk(target):
        for d in dirs:
            if d.startswith("."):
                convert_courier_indexed_list(os.path.join(root, d))
