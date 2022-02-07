{{- define "app_env" }}
- name: TZ
  value: "Europe/Moscow"
- name: DB_USER
  value: {{ pluck .Values.werf.env .Values.mysql.username | first | default .Values.mysql.username._default | quote }}
- name: DB_PASSWD
  value: {{ pluck .Values.werf.env .Values.mysql.password | first | default .Values.mysql.password._default | quote }}
- name: DB_NAME
  value: {{ pluck .Values.werf.env .Values.mysql.database | first | default .Values.mysql.database._default | quote }}
- name: DB_HOST
  value: {{ pluck .Values.werf.env .Values.mysql.host | first | default .Values.mysql.host._default | quote }}
- name: YANDEX_RASP_KEY
  value: {{ pluck .Values.werf.env .Values.services.yandex_rasp.key | first | default .Values.services.yandex_rasp.key._default | quote }}
{{- end }}
