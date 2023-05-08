#!/usr/bin/perl

use strict;

package main;


############################        General Functions 

sub max
	{
		my ($max_so_far) = shift @_;
		foreach  (@_) 
			{
				if ($_ > $max_so_far) 
					{
						$max_so_far = $_;
					}
			}
		$max_so_far;
	}

sub by_number
{
	if (($a*1) < ($b*1)) { -1 } elsif (($a*1) >= ($b*1)) { 1 } else { 0 }
}

sub arraySum
	{
		my (@array) = @_;
		
		my ($arrval, $arrsum) = (0, 0);

		foreach $arrval (@array) 
			{
				$arrsum = $arrsum + $arrval;
			}
		$arrsum;	
	}


#////////////    End of General Functions

open debug, ">Debug_log";

#print debug "\n\n######## Start of the program output ############\n\n";

#my $inpfile1 = $ARGV[0];
#my $totpartlines = $ARGV[1];

####### Read the list of multiple files, capture directory and file name information and the output file index

my $numargs = $#ARGV + 1;
my $argct = 1;
my @files;
my $outfileindexarg = 2;
my $Functionalcode = "F";
my $metaFunctionalcode = quotemeta $Functionalcode;
my $Productivecode = "productive";
my $metaProductivecode = quotemeta "productive";
my $topxrank = 100;
#my $topxpct = 50;
my $topxpct = 0.25;
my $lowxpct = 0.1;
#my $topxpct = 1.00;
my $filedelim = "\t";
# my $filedelim = ",";


my $clonclustmism = 3;

my $filemetad;
my $headername;

my $outfilenameindex = $ARGV[$outfileindexarg-1];

#my $metaoutfilenameindex = quotemeta $outfilenameindex;
# $metaoutfilenameindex =~ /^(.*)\.([\w\-]*)$/i;

$outfilenameindex =~ /^(.*)\.([\w\-]*)$/i;
#$metaoutfilenameindex =~ /^(.*)(\/)([\w\-]*)\.([\w\-]*)$/i;
#$metaoutfilenameindex =~ /^(((.*)(\/))[\w\-]*)\.([\w\-]*)$/i;

$outfilenameindex = $1;
my $outfileextn = $2;
#$outfileextn = "txt";


print debug "\noutfilenameix = $outfilenameindex\toutfileextn = $outfileextn";

print debug "\n1 = $1\t2 = $2\t3 = $3\4 = $4\5 = $5\n\n\n";


#@clonetypes = ("CDR3AA_vgene_jgene");


$outfilenameindex =~ s/\\//gi;
#$outfiledir =~ s/\\//gi;
$outfilenameindex =~ /^(.*\/)([._\w\-]*)$/i;
my $outfilepathdir = $1; 
$outfilenameindex = $2;

print debug "\nAfter the outfile dir 1 = $1\t2 = $2\n\n";

#my $filenameindex = "Summary";


my $groups;
my @groupsnordr;

my $validannotseqs;


foreach my $currarg (@ARGV)
{

	if ($argct == 1)
	{
		@files = glob $currarg;
		my $sampidct = 0;
		print debug "\n files are: @files";
		print debug "\n curr arg  = $currarg";
		foreach my $currfile (@files)
		{
			print debug "\n######## Current file is: $currfile";
						
			my $filefullpath = "./".($currfile);
			my $metafilefullpath = quotemeta $filefullpath;

			$metafilefullpath =~ /^(.*\/)(.*)\.([_\w\-]*)$/i;

			my $filepath = $1;
			my $filename = $2;
			my $fileextn = $3;

			print debug "\n\nBefore filedir 1 = $1\t2 = $2\t3 = $3\4 = $4\5 = $5";
			
			$filepath =~ s/\\//gi;
			#$outfiledir =~ s/\\//gi;
			$filepath =~ /^(.*\/)([._\w\-]*)\/$/i;
			my $filedir = $2;
			
			print debug "\nAfter the file dir 1 = $1\t2 = $2";
			
			$filename =~ s/\\//gi;
			
			$filemetad->{fname}->{$filename}->{name} = $filename;
			$filemetad->{fname}->{$filename}->{extn} = $fileextn;
			$filemetad->{fname}->{$filename}->{fpath} = $filepath;
			$filemetad->{fname}->{$filename}->{fdir} = $filedir;
#			$filemetad->{fname}->{$filename}->{fullpath} = $currfile;
			$filemetad->{fname}->{$filename}->{fullpath} = $filefullpath;
#			$filemetad->{fname}->{$filename}->{outpath} = ($outfilepathdir).($filedir)."/";
			$filemetad->{fname}->{$filename}->{outpath} = ($outfilepathdir);
			$filemetad->{fname}->{$filename}->{outfullfnameidx} = ($outfilenameindex).".".($outfileextn);

			print debug "\n\nline 313\t## \tfilename = $currfile";

##########			

			print debug "\nfilepath = $filepath\tfilename = $filename\t fileextn = $fileextn \tfiledir = $filedir";
			print debug "\nfor the pointer variables\nfullpath = ",$filemetad->{fname}->{$filename}->{fullpath},"\tfilepath = ",$filemetad->{fname}->{$filename}->{fpath},"\tfilename = ",$filemetad->{fname}->{$filename}->{name},"\t fileextn = ",$filemetad->{fname}->{$filename}->{extn}," \tfiledir = ",$filemetad->{fname}->{$filename}->{fdir},"\toutfilepath = ",$filemetad->{fname}->{$filename}->{outpath},"\toutfullfilename = ",$filemetad->{fname}->{$filename}->{outfullfnameidx},"\n";
		} 	
	}

	print debug "\n currarg = $currarg";
	$argct++;	
}

#//////// End of Read the list of multiple files, capture directory and file name information and the output file index

##### Parse each files and process if necessary
#foreach my $currgroup ("Allsamples", sort by_number keys %{$groups})
	my @currfiles;
	
	my %currhdrref;
	my $inpfile;
#		my @outfiles;
foreach my $currfile (keys %{$filemetad->{fname}})
{
	my %currhdrref;
	my $inpfile;
	my $currfilename;

	$inpfile = ($filemetad->{fname}->{$currfile}->{fullpath});
	$currfilename = "./".($filemetad->{fname}->{$currfile}->{name});
	#		$outfiles[0] = ($filemetad->{fname}->{$currfile}->{outpath}).($filemetad->{fname}->{$currfile}->{outfullfnameidx});
	#		my $currfilename = ($filemetad->{fname}->{$currfile}->{name});

	#		open currsumryfile, "<$inpfile";
	open currjuncfile, "<$inpfile";

	my $currfilehdr = <currjuncfile>;
	chomp($currfilehdr);

#  	%currhdrref = &parseheaders($currfilehdr, "\t");
  	%currhdrref = &parseheaders($currfilehdr, $filedelim);

	my $Vgenecolno = $currhdrref{header}->{"vgene_allele"}-1;
	my $fnalitycolno = $currhdrref{header}->{"functionality"} - 1;
	my $juncaaseq = $currhdrref{header}->{"junction_sequence_aa"} - 1;
	my $Jgenecolno = $currhdrref{header}->{"jgene_allele"}-1;
	my $Dgenecolno = $currhdrref{header}->{"dgene_allele"}-1;
	my $SeqIDcolno = $currhdrref{header}->{"seq_id"}-1;

	mkdir $currfilename;
	my $sampletempout = "./$currfilename/Sample1outputtemp.txt";

	open samplefiletemp, ">$sampletempout";
	print samplefiletemp "Seq ID\tfunctionality\tvgene\tjgene\tdgene\tjunction";

	while (<currjuncfile>)
		{
			chomp;

			my $currseqinfo = $_;	
			my @currseqvals = &Splitrow($currseqinfo, $filedelim);
# 			my @currseqvals = &Splitrow($currseqinfo,"\t");
			
		
			my $currvgene = $currseqvals[$Vgenecolno];
			my $fnality = $currseqvals[$fnalitycolno];
			my $currjgene = $currseqvals[$Jgenecolno];
			my $currdgene = $currseqvals[$Dgenecolno];
			my $currcdr3aa = $currseqvals[$juncaaseq];
			my $currseqid = $currseqvals[$SeqIDcolno];
		
			print samplefiletemp "\n$currseqid\t$fnality\t$currvgene\t$currjgene\t$currdgene\t$currcdr3aa";
		
		}		
}

close debug;
close samplefiletemp;

	
	####### for debug 8 Apr print debug "line 1881: End of the group loop: currgroup = $currgroup";

######## for debug 8 Apr print debug "\n\n//////// End of the program output //////////";
######## for debug 8 Apr print debug "\n\n\n";

######## Specific Functions

sub Splitrow
{
	my @params = @_;
	my $i = 0;
	my $temprow = $params[0];
	
	my $tempdelim = quotemeta $params[1];
	########## for debug 8 Apr print debug "\nLine 163: tempdelim = $tempdelim";
	#my @splitrows = split /$params[1]/, $temprow;
	#my @Rowsplits = split /$params[1]/, $temprow;
	my @Rowsplits = split /$tempdelim/, $temprow;
	
	foreach my $splits (@Rowsplits) 
		{
			$Rowsplits[$i] =~ s/\"//gi;
			$Rowsplits[$i] = &Trim($Rowsplits[$i]); 
			$i++;
		}
@Rowsplits;
}

sub Trim
{
	my $term = $_[0];
	$_ = $term;
	s/^ *//;
	s/ *$//;
	########### for debug 8 Apr print debug "In the Seq imgt while and Seqsplits for loop, before mod Seqsplits = $Seqsplits\n";
	########### for debug 8 Apr print debug "In the Seq imgt while and Seqsplits for loop, currSeqsplits = $_ \n";
	my $trimterm = $_;
	$trimterm;
}

sub parseheaders
{
	my ($parseheader, $delim) = @_;
	my @headers = &Splitrow($parseheader, $delim);
	my %headerinfo;
	#my $headername;
	my $headerct = 1;
	
	foreach my $currheader (@headers)  
	{
		$headerinfo{header}->{$currheader} = $headerct;
		$headerinfo{headercolno}->{$headerct} = $currheader;
		$headerct++;
	}
	(%headerinfo);	
}

sub parseIMGTgeneallele
{

###### Typical gene value example: Homsap IGHV3-23*01 F, or Homsap IGHV3-23*04 F or Homsap IGHV3-23D*01 F
###### Another example: Homsap IGHV4-34*01 F, or Homsap IGHV4-34*02 F or Homsap IGHV4-34*03 F or Homsap IGHV4-34*04 P or Homsap IGHV4-34*06 F (see comment)

	my ($geneval, $delim1, $delim2) = @_;
	
	my ($IMGTunambiglocusfam, $IMGTunambiggenefam, $IMGTunambigalleltyp);


	my ($genefamambigflag, $locusfamambigflag, $alleletypambigflag) = (0, 0, 0);
	my $genegrpct;
	
	my @allelegrps = &Splitrow($geneval, $delim1);
	my $delim3 = "or";
	
	######### for debug 8 Apr print debug "\n\n**********CurrGenevalue = $geneval\n\n###############\n";
	
	foreach my $currallelegrp (@allelegrps)
	{
		
		my $metadelim3 = quotemeta $delim3;
		
		$currallelegrp =~ s/^ *($metadelim3) *//i;
		$currallelegrp =~ s/ *($metadelim3) *$//i;
		
		my @ambigallelevals = &Splitrow($currallelegrp, $delim3);
		
# 		my %currgrplocusfamct;
# 		my %currgrpgenefamct; 
		my ($currgrplocusfamct, $currgrpgenefamct) = (0, 0); 
		
#		print "\nCurrallegrp = ###### $currallelegrp ######\n";
		
		foreach my $currambigallele (@ambigallelevals)
		{
			$currambigallele =~ /(\(.*\))/i;
			my $specialannot = $1;
			$specialannot = "" if (!($currambigallele =~ /\(/i));
			
			my @curralleleelemns = &Splitrow($currambigallele, $delim2);
			my $species = $curralleleelemns[0];
			my $allele = $curralleleelemns[1];
			my $alleletyp = $curralleleelemns[2];
			
			my ($curralelchain, $curralellocus, $curralellocusfamily, $curralelgene, $curralelgenefam) = &parseIMGTallele($allele);
			
#			print "\n## currambigallele = $currambigallele\t# specialannot = $specialannot\t# species = $species\t# allele = $allele\t# alleletyp = $alleletyp";
#			print "\n\n## curralelchain = $curralelchain\t# curralellocus = $curralellocus\t# curralellocusfamily = $curralellocusfamily\t# curralelgene = $curralelgene\t# curralelgenefam = $curralelgenefam";

# 			if (!(defined($genegrpct->{$currallelegrp}->{locusfam}->{$curralellocusfamily})))
# 			{			
# 				$genegrpct->{$currallelegrp}->{locusfam}->{$curralellocusfamily} = $genegrpct->{$currallelegrp}->{locusfam}->{$curralellocusfamily} + 1;
# 				$genegrpct->{$currallelegrp}->{locusfamct} = $genegrpct->{$currallelegrp}->{locusfamct} + 1;
# 			}
# 			if (!(defined($genegrpct->{$currallelegrp}->{genefam}->{$curralelgenefam})))
# 			{
# 				$genegrpct->{$currallelegrp}->{genefam}->{$curralelgenefam} = $genegrpct->{$currallelegrp}->{genefam}->{$curralelgenefam} + 1;
# 				$genegrpct->{$currallelegrp}->{genefamct} = $genegrpct->{$currallelegrp}->{genefamct} + 1;
# 			}

			if (!(defined($genegrpct->{locusfam}->{$curralellocusfamily})))
			{			
				$genegrpct->{locusfam}->{$curralellocusfamily} = $genegrpct->{locusfam}->{$curralellocusfamily} + 1;
				$genegrpct->{locusfamct} = $genegrpct->{locusfamct} + 1;
			}
			if (!(defined($genegrpct->{genefam}->{$curralelgene})))
			{
				$genegrpct->{genefam}->{$curralelgene} = $genegrpct->{genefam}->{$curralelgene} + 1;
				$genegrpct->{genefamct} = $genegrpct->{genefamct} + 1;				
			}
			if (!(defined($genegrpct->{alleletype}->{$alleletyp})))
			{
				$genegrpct->{alleletype}->{$alleletyp} = $genegrpct->{alleletype}->{$alleletyp} + 1;
				$genegrpct->{alleletypect} = $genegrpct->{alleletypect} + 1;				
			}

# 			$currgrplocusfamct{$curralellocusfamily}++;
# 			$currgrpgenefamct{$curralelgenefam}++;

			$IMGTunambiglocusfam = $curralellocusfamily;
			$IMGTunambiggenefam = $curralelgene;
			$IMGTunambigalleltyp = $alleletyp;
			
			######### for debug 8 Apr print debug "\n\nIMGTunambiglocusfam = $IMGTunambiglocusfam\t\t IMGTunambiggenefam = $IMGTunambiggenefam\t\t IMGTunambigalleltyp = $IMGTunambigalleltyp\n\n";
		}
		
#		$currgrplocusfamct = keys %{$genegrpct->{$currallelegrp}->{locusfam}});
#		$currgrpgenefamct = keys %{$genegrpct->{$currallelegrp}->{genefam}});
		
# 		if ($currgrplocusfamct > 1)
# 		{
# 			$locusfamambigflag++;			
# 		}
# 		if ($currgrpgenefamct > 1)
# 		{
# 			$genefamambigflag++;
# 		}

		if ($genegrpct->{locusfamct} > 1)
		{
			$locusfamambigflag++;			
		}
		if ($genegrpct->{genefamct} > 1)
		{
			$genefamambigflag++;
		}
		if ($genegrpct->{alleletypect} > 1)
		{
			$alleletypambigflag++;
		}

		######### for debug 8 Apr print debug "\n\n locusfamct = ",$genegrpct->{locusfamct},"\t\t genefamct = ",$genegrpct->{genefamct},"\t\t alleletypect = ",$genegrpct->{alleletypect},"\n\n";

		######### for debug 8 Apr print debug "\n\nEnd of ////// $currallelegrp /////////\n";
	}
	
	my $ambigcode = 0;
	if ($locusfamambigflag >= 1)
	{
		$IMGTunambiglocusfam = "Ambiguous locus family";
		$ambigcode = 1;
	}
	if ($genefamambigflag >= 1)
	{
		$IMGTunambiggenefam = "Ambiguous gene family";
		$ambigcode = $ambigcode+2;
	}	
	if ($alleletypambigflag >= 1)
	{
		$IMGTunambigalleltyp = "Ambiguous allele type";
		$ambigcode = $ambigcode+4;
	}
	
	($IMGTunambiglocusfam, $IMGTunambiggenefam, $IMGTunambigalleltyp, $ambigcode);
}

sub parseIMGTallele
{
	my $allele = $_[0];
#	######## for debug 8 Apr print debug "\n Inside the parse IMGT alelle and the allele before trim = $allele";

	my $assignmatchvars = "";	
	$assignmatchvars =~ /(((((())))))/i;

	my $trimdallele = &Trim($allele);
#	######## for debug 8 Apr print debug "\n Inside the parse IMGT alelle and the allele after trim = $trimdallele";

	$trimdallele =~ /^([A-Za-z]+)((([0-9]*)([\-\w]*))([\*0-9]*))$/i;

	my ($locus, $genealleleno, $geneno, $genefamilyno, $subgeneno, $alleleno) = ($1, $2, $3, $4, $5, $6);	
	######### for debug 8 Apr print debug "\n*****allele is $allele\tTrimmedallele = $trimdallele\t\t**** locus = $1, genealleleno = $2, geneno = $3, genefamilyno = 4, subgeneno = $5, alleleno = $5";

	$subgeneno =~ s/^\-//i;
	$alleleno =~ s/^\*//i;

	my @subgenenos = &Splitrow($subgeneno,"-");
	my $subgenefam = ($locus).($genefamilyno)."-".($subgenenos[0]);
	$subgenefam = ($locus).($genefamilyno) if ($subgeneno =~ /^ *$/i);
	my $genefamily = ($locus).($genefamilyno);
	my $gene = ($locus).($geneno);
	my $chain = $locus;
	$chain =~ s/[\w]$//i;
	
#	######## for debug 8 Apr print debug "\n\n////////// $chain, $locus, $genefamily, $gene, $subgenefam /////////\n\n";
	
	($chain, $locus, $genefamily, $gene, $subgenefam);
}
#//////// End of Specific Functions