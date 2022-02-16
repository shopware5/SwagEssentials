FROM mysql:5.7

ADD dev.cnf /etc/mysql/conf.d/dev.cnf
ADD remote-access.cnf /etc/mysql/conf.d/remote-access.cnf
ADD performance-schema.cnf /etc/mysql/conf.d/performance-schema.cnf
COPY createuser.sh /tmp/createuser.sh
RUN chmod +rwx /tmp/createuser.sh
RUN /tmp/createuser.sh